// lib/features/quiz/providers/quiz_provider.dart
import 'dart:async';
import 'package:flutter/foundation.dart';
import '../data/models/question.dart';
import '../data/models/quiz_attempt.dart';
import '../data/models/attempt_resume.dart';
import '../data/quiz_result_model.dart';
import '../../../services/quiz_attempt_service.dart';
import '../../../services/attempt_history_service.dart';
import '../../../core/exceptions/api_exception.dart';

enum QuizStatus { idle, loading, active, finished, error }

class QuizProvider extends ChangeNotifier {
  final QuizAttemptService _attemptService;
  final AttemptHistoryService _historyService = AttemptHistoryService();

  QuizProvider(this._attemptService);

  QuizStatus _status = QuizStatus.idle;
  List<QuestionModel> _questions = [];
  int _currentIndex = 0;
  final Map<int, List<int>> _selectedOptionIds = {}; // questionIndex -> optionIds
  final Map<int, String> _textAnswers = {}; // questionIndex -> text
  final Map<int, bool> _bookmarks = {}; // questionIndex -> bookmarked
  int _categoryId = 0;
  String _categoryName = '';
  QuizResultModel? _lastResult;
  String? _errorMessage;
  QuizAttempt? _attempt;
  Timer? _ticker;
  int _remainingSeconds = 0;

  QuizStatus get status => _status;
  List<QuestionModel> get questions => _questions;
  int get currentIndex => _currentIndex;
  QuestionModel? get currentQuestion =>
      _questions.isEmpty ? null : _questions[_currentIndex];
  int get totalQuestions => _questions.length;
  bool get isLastQuestion => _currentIndex == _questions.length - 1;
  Map<int, List<int>> get answers =>
      _selectedOptionIds.map((key, value) => MapEntry(key, List.unmodifiable(value)));
  Map<int, String> get textAnswers => Map.unmodifiable(_textAnswers);
  Map<int, bool> get bookmarks => Map.unmodifiable(_bookmarks);
  QuizResultModel? get lastResult => _lastResult;
  String? get errorMessage => _errorMessage;
  bool get hasEverTakenQuiz => _lastResult != null;
  int get remainingSeconds => _remainingSeconds;
  QuizAttempt? get attempt => _attempt;
  bool get isExpired => _remainingSeconds <= 0;
  bool get allowReviewBeforeSubmit => _attempt?.allowReviewBeforeSubmit ?? true;

  bool isQuestionAnswered(int index) {
    if (index < 0 || index >= _questions.length) return false;
    final question = _questions[index];
    if (question.questionType == 'short_answer') {
      return (_textAnswers[index] ?? '').trim().isNotEmpty;
    }
    return (_selectedOptionIds[index] ?? const <int>[]).isNotEmpty;
  }

  bool isBookmarked(int index) => _bookmarks[index] ?? false;

  int get answeredCount => List.generate(_questions.length, (i) => i)
      .where(isQuestionAnswered)
      .length;

  int get bookmarkedCount => _bookmarks.values.where((value) => value).length;

  int get unansweredCount => _questions.length - answeredCount;

  String get timerLabel {
    final secs = _remainingSeconds < 0 ? 0 : _remainingSeconds;
    final minutes = secs ~/ 60;
    final seconds = secs % 60;
    final m = minutes.toString().padLeft(2, '0');
    final s = seconds.toString().padLeft(2, '0');
    return '$m:$s';
  }

  /// Starts quiz attempt and loads questions
  Future<void> startQuiz(int categoryId, String categoryName) async {
    _status = QuizStatus.loading;
    _errorMessage = null;
    _categoryId = categoryId;
    _categoryName = categoryName;
    notifyListeners();

    try {
      final response = await _attemptService.startAttempt(
        categoryId: categoryId,
      );
      _attempt = response.attempt;
      _questions = response.questions;
      _currentIndex = 0;
      _selectedOptionIds.clear();
      _textAnswers.clear();
      _bookmarks.clear();

      _applySavedAnswers(response.savedAnswers);
      _restoreProgress(response.progress);

      _remainingSeconds = _attempt?.remainingSeconds ?? 0;
      _startTicker();

      if (_questions.isEmpty) {
        _status = QuizStatus.error;
        _errorMessage = 'No questions found for this category';
      } else {
        _status = QuizStatus.active;
      }
    } on ApiException catch (e) {
      if (e.statusCode == 409 && e.type == 'active_attempt_exists') {
        _status = QuizStatus.error;
        _errorMessage =
            'An active attempt already exists for this quiz. Tap to continue?';
      } else {
        _status = QuizStatus.error;
        _errorMessage = 'Failed to load quiz: ${e.message}';
      }
    } catch (e) {
      _status = QuizStatus.error;
      _errorMessage = 'Failed to load quiz: $e';
    }
    notifyListeners();
  }

  /// Student selects an option answer for the current question
  Future<void> answerQuestion(int optionIndex) async {
    if (_attempt == null) return;
    if (isExpired) return;

    final question = currentQuestion;
    if (question == null) return;

    final optionId = question.options[optionIndex].id;
    _selectedOptionIds[_currentIndex] = [optionId];
    notifyListeners();

    await _attemptService.saveAnswer(
      attemptId: _attempt!.id,
      questionId: question.id,
      optionId: optionId,
    );
  }

  Future<void> toggleMultiSelectOption(int optionIndex) async {
    if (_attempt == null) return;
    if (isExpired) return;

    final question = currentQuestion;
    if (question == null || question.questionType != 'multi_select') return;

    final optionId = question.options[optionIndex].id;
    final selected = List<int>.from(_selectedOptionIds[_currentIndex] ?? const <int>[]);

    if (selected.contains(optionId)) {
      if (selected.length == 1) {
        return;
      }
      selected.remove(optionId);
    } else {
      selected.add(optionId);
    }

    selected.sort();
    _selectedOptionIds[_currentIndex] = selected;
    notifyListeners();

    await _attemptService.saveAnswer(
      attemptId: _attempt!.id,
      questionId: question.id,
      optionIds: selected,
    );
  }

  List<int> selectedOptionIdsFor(int index) =>
      List.unmodifiable(_selectedOptionIds[index] ?? const <int>[]);

  bool isOptionSelected(int questionIndex, int optionId) =>
      (_selectedOptionIds[questionIndex] ?? const <int>[]).contains(optionId);

  void toggleBookmark({int? questionIndex}) {
    if (_attempt == null) return;
    if (isExpired) return;

    final index = questionIndex ?? _currentIndex;
    if (index < 0 || index >= _questions.length) return;
    final question = _questions[index];

    final current = _bookmarks[index] ?? false;
    final next = !current;
    _bookmarks[index] = next;
    notifyListeners();

    _attemptService.saveAnswer(
      attemptId: _attempt!.id,
      questionId: question.id,
      isBookmarked: next,
    );
  }

  /// Student submits a short answer for the current question
  Future<void> answerText(String text) async {
    if (_attempt == null) return;
    if (isExpired) return;

    final question = currentQuestion;
    if (question == null) return;

    _textAnswers[_currentIndex] = text;
    notifyListeners();

    await _attemptService.saveAnswer(
      attemptId: _attempt!.id,
      questionId: question.id,
      textAnswer: text,
    );
  }

  /// Move to next question or finish
  void nextQuestion() {
    if (_currentIndex < _questions.length - 1) {
      _currentIndex++;
    } else {
      _finishQuiz();
    }
    notifyListeners();
  }

  /// Move to previous question
  void previousQuestion() {
    if (_currentIndex > 0) {
      _currentIndex--;
      notifyListeners();
    }
  }

  void _finishQuiz() {
    // The final score is computed server-side on submit.
    _status = QuizStatus.finished;
    _stopTicker();
  }

  void reset({bool clearLastResult = false}) {
    _status = QuizStatus.idle;
    _currentIndex = 0;
    _questions = [];
    _selectedOptionIds.clear();
    _textAnswers.clear();
    _bookmarks.clear();
    _categoryId = 0;
    _categoryName = '';
    _errorMessage = null;
    _attempt = null;
    _remainingSeconds = 0;
    if (clearLastResult) {
      _lastResult = null;
    }
    _stopTicker();
    notifyListeners();
  }

  Future<void> submitAttempt() async {
    if (_attempt == null) return;

    try {
      final response =
          await _attemptService.submitAttempt(attemptId: _attempt!.id);
      final data = (response['data'] as Map<String, dynamic>?) ?? {};
      final score = (data['score'] as Map<String, dynamic>?) ?? {};

      final totalItems = score['total_items'] as int? ?? _questions.length;
      final correct = score['correct_answers'] as int? ?? 0;
      final percent = (score['score_percent'] as num?)?.toInt() ?? 0;

      _lastResult = QuizResultModel(
        categoryId: _categoryId,
        categoryName: _categoryName,
        totalQuestions: totalItems,
        correctAnswers: correct,
        scorePercentOverride: percent,
        takenAt: DateTime.now(),
      );

      _status = QuizStatus.finished;
      _stopTicker();
    } catch (e) {
      _status = QuizStatus.error;
      _errorMessage = 'Failed to submit quiz: $e';
    }
    notifyListeners();
  }

  void _startTicker() {
    _stopTicker();
    if (_remainingSeconds <= 0) return;
    _ticker = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_remainingSeconds <= 0) {
        _remainingSeconds = 0;
        _stopTicker();
        notifyListeners();
        return;
      }
      _remainingSeconds--;
      notifyListeners();
    });
  }

  void _stopTicker() {
    _ticker?.cancel();
    _ticker = null;
  }

  void jumpToQuestion(int index) {
    if (index < 0 || index >= _questions.length) return;
    _currentIndex = index;
    notifyListeners();
  }

  void _applySavedAnswers(List<AttemptSavedAnswer> savedAnswers) {
    if (savedAnswers.isEmpty) return;

    final indexByQuestionId = <int, int>{};
    for (var i = 0; i < _questions.length; i++) {
      indexByQuestionId[_questions[i].id] = i;
    }

    for (final saved in savedAnswers) {
      final index = indexByQuestionId[saved.questionId];
      if (index == null) continue;

      if (saved.optionId != null) {
        _selectedOptionIds[index] = [saved.optionId!];
      }
      if (saved.selectedOptionIds.isNotEmpty) {
        _selectedOptionIds[index] = List<int>.from(saved.selectedOptionIds)..sort();
      }
      final text = saved.textAnswer ?? saved.answer;
      if (text != null && text.isNotEmpty) {
        _textAnswers[index] = text;
      }
      if (saved.isBookmarked != null) {
        _bookmarks[index] = saved.isBookmarked!;
      }
    }
  }

  void _restoreProgress(AttemptProgress? progress) {
    final index = progress?.lastViewedQuestionIndex;
    if (index == null || index < 0 || index >= _questions.length) return;
    _currentIndex = index;
  }

  Future<void> loadLatestResult({bool force = false}) async {
    if (_status == QuizStatus.loading) return;
    if (!force && _lastResult != null) return;

    try {
      final histories = await _historyService.getHistory(perPage: 50);
      if (histories.isEmpty) {
        if (force && _lastResult != null) {
          _lastResult = null;
          notifyListeners();
        }
        return;
      }

      histories.sort((a, b) {
        final aDate = a.submittedAt ??
            a.startedAt ??
            DateTime.fromMillisecondsSinceEpoch(0);
        final bDate = b.submittedAt ??
            b.startedAt ??
            DateTime.fromMillisecondsSinceEpoch(0);
        return bDate.compareTo(aDate);
      });

      final latest = histories.first;
      _lastResult = QuizResultModel(
        categoryId: latest.categoryId,
        categoryName: latest.categoryName,
        totalQuestions: latest.totalItems,
        correctAnswers: latest.correctAnswers,
        scorePercentOverride: latest.scorePercent.round(),
        takenAt: latest.submittedAt ?? latest.startedAt ?? DateTime.now(),
      );
      notifyListeners();
    } catch (_) {
      // Keep the current UI stable if history hydration fails.
    }
  }
}
