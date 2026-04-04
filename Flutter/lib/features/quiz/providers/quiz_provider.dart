// lib/features/quiz/providers/quiz_provider.dart
import 'dart:async';
import 'package:flutter/foundation.dart';
import '../data/models/question.dart';
import '../data/models/quiz_attempt.dart';
import '../data/models/attempt_resume.dart';
import '../data/quiz_result_model.dart';
import '../../../services/quiz_attempt_service.dart';
import '../../../core/exceptions/api_exception.dart';

enum QuizStatus { idle, loading, active, finished, error }
enum SaveStatus { idle, saving, saved, retrying, offline }

class QuizProvider extends ChangeNotifier {
  final QuizAttemptService _attemptService;
  final Duration _autosaveDebounce;

  QuizProvider(this._attemptService, {Duration autosaveDebounce = const Duration(milliseconds: 500)})
      : _autosaveDebounce = autosaveDebounce;

  QuizStatus            _status       = QuizStatus.idle;
  List<QuestionModel>   _questions    = [];
  int                   _currentIndex = 0;
  final Map<int, int>   _selectedOptionIds = {}; // questionIndex -> optionId
  final Map<int, Set<int>> _selectedOptionIdSets = {}; // questionIndex -> optionIds
  final Map<int, String> _textAnswers = {}; // questionIndex -> text
  final Map<int, bool> _bookmarks = {}; // questionIndex -> bookmarked
  int                   _categoryId   = 0;
  String                _categoryName = '';
  QuizResultModel?      _lastResult;
  String?               _errorMessage;
  QuizAttempt?          _attempt;
  Timer?                _ticker;
  int                   _remainingSeconds = 0;
  SaveStatus            _saveStatus = SaveStatus.idle;
  Timer?                _autosaveTimer;
  Timer?                _retryTimer;
  AutosavePayload?      _pendingAutosave;
  int                   _autosaveFailures = 0;

  QuizStatus            get status        => _status;
  List<QuestionModel>   get questions     => _questions;
  int                   get currentIndex  => _currentIndex;
  QuestionModel?        get currentQuestion =>
      _questions.isEmpty ? null : _questions[_currentIndex];
  int                   get totalQuestions => _questions.length;
  bool                  get isLastQuestion => _currentIndex == _questions.length - 1;
  Map<int, int>         get answers       => Map.unmodifiable(_selectedOptionIds);
  Map<int, Set<int>>    get multiAnswers  =>
      Map.unmodifiable(_selectedOptionIdSets.map((key, value) => MapEntry(key, Set.of(value))));
  Map<int, String>      get textAnswers   => Map.unmodifiable(_textAnswers);
  Map<int, bool>        get bookmarks     => Map.unmodifiable(_bookmarks);
  QuizResultModel?      get lastResult    => _lastResult;
  String?               get errorMessage  => _errorMessage;
  bool                  get hasEverTakenQuiz => _lastResult != null;
  int                   get remainingSeconds => _remainingSeconds;
  QuizAttempt?          get attempt => _attempt;
  SaveStatus            get saveStatus => _saveStatus;
  bool                  get isSubmitted => _attempt?.submittedAt != null ||
      _attempt?.status == 'submitted' ||
      _attempt?.status == 'completed' ||
      _attempt?.status == 'graded';
  bool                  get isExpired => _remainingSeconds <= 0 ||
      _attempt?.status == 'expired';
  bool                  get isLocked => isExpired || isSubmitted;

  String get saveStatusLabel {
    switch (_saveStatus) {
      case SaveStatus.saving:
        return 'Saving...';
      case SaveStatus.saved:
        return 'Saved';
      case SaveStatus.retrying:
        return 'Retrying save...';
      case SaveStatus.offline:
        return 'Offline';
      case SaveStatus.idle:
      default:
        return '';
    }
  }

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
      _selectedOptionIds.clear();
      _selectedOptionIdSets.clear();
      _textAnswers.clear();
      _bookmarks.clear();
      _saveStatus = SaveStatus.idle;

      _applySavedAnswers(response.savedAnswers);
      _restoreProgress(response.progress);

      _remainingSeconds = _attempt?.remainingSeconds ?? 0;
      if (!isLocked) {
        _startTicker();
      } else {
        _stopTicker();
      }

      if (_questions.isEmpty) {
        _status = QuizStatus.error;
        _errorMessage = 'No questions found for this category';
      } else {
        _status = QuizStatus.active;
      }
    } on ApiException catch (e) {
      if (e.statusCode == 409 && e.type == 'active_attempt_exists') {
        _status = QuizStatus.error;
        _errorMessage = 'An active attempt already exists for this quiz. Tap to continue?';
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
  void answerQuestion(int optionIndex) {
    if (_attempt == null) return;
    if (isLocked) return;

    final question = currentQuestion;
    if (question == null) return;

    final optionId = question.options[optionIndex].id;
    _selectedOptionIds[_currentIndex] = optionId;
    notifyListeners();

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        optionId: optionId,
      ),
    );
  }

  /// Student toggles a multi-select option for the current question
  void answerQuestionMulti(int optionIndex) {
    if (_attempt == null) return;
    if (isLocked) return;

    final question = currentQuestion;
    if (question == null) return;

    final optionId = question.options[optionIndex].id;
    final selectedSet = _selectedOptionIdSets[_currentIndex] ??= <int>{};

    if (selectedSet.contains(optionId)) {
      selectedSet.remove(optionId);
    } else {
      selectedSet.add(optionId);
    }

    notifyListeners();

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        optionIds: List<int>.from(selectedSet),
      ),
    );
  }

  /// Student submits a short answer for the current question
  void answerText(String text) {
    if (_attempt == null) return;
    if (isLocked) return;

    final question = currentQuestion;
    if (question == null) return;

    _textAnswers[_currentIndex] = text;
    notifyListeners();

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        textAnswer: text,
      ),
    );
  }

  void updateTextDraft(String text) {
    if (_attempt == null) return;
    if (isLocked) return;

    final question = currentQuestion;
    if (question == null) return;

    _textAnswers[_currentIndex] = text;
    notifyListeners();

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        textAnswer: text,
      ),
    );
  }

  void toggleBookmark() {
    if (_attempt == null) return;
    if (isLocked) return;

    final question = currentQuestion;
    if (question == null) return;

    final current = _bookmarks[_currentIndex] ?? false;
    final next = !current;
    _bookmarks[_currentIndex] = next;
    notifyListeners();

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        isBookmarked: next,
      ),
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
    _autosaveProgress();
  }

  /// Move to previous question
  void previousQuestion() {
    if (_currentIndex > 0) {
      _currentIndex--;
      notifyListeners();
      _autosaveProgress();
    }
  }

  void _finishQuiz() {
    // The final score is computed server-side on submit.
    _status = QuizStatus.finished;
    _stopTicker();
  }

  void reset({bool clearLastResult = false}) {
    _status       = QuizStatus.idle;
    _currentIndex = 0;
    _questions = [];
    _selectedOptionIds.clear();
    _selectedOptionIdSets.clear();
    _textAnswers.clear();
    _bookmarks.clear();
    _categoryId = 0;
    _categoryName = '';
    _errorMessage = null;
    _attempt = null;
    _remainingSeconds = 0;
    _saveStatus = SaveStatus.idle;
    _pendingAutosave = null;
    _autosaveFailures = 0;
    if (clearLastResult) {
      _lastResult = null;
    }
    _stopTicker();
    _cancelAutosaveTimers();
    notifyListeners();
  }

  Future<void> submitAttempt() async {
    if (_attempt == null) return;

    try {
      final response = await _attemptService.submitAttempt(attemptId: _attempt!.id);
      final data = (response['data'] as Map<String, dynamic>?) ?? {};
      final score = (data['score'] as Map<String, dynamic>?) ?? {};

      final totalItems = score['total_items'] as int? ?? _questions.length;
      final correct = score['correct_answers'] as int? ?? 0;
      final percent = (score['score_percent'] as num?)?.toInt() ?? 0;

      _lastResult = QuizResultModel(
        categoryId:     _categoryId,
        categoryName:   _categoryName,
        totalQuestions: totalItems,
        correctAnswers: correct,
        scorePercentOverride:   percent,
        takenAt:        DateTime.now(),
      );

      _status = QuizStatus.finished;
      if (_attempt != null) {
        _attempt = _attempt!.copyWith(
          status: 'submitted',
          submittedAt: DateTime.now(),
          remainingSeconds: 0,
        );
        _remainingSeconds = 0;
      }
      _stopTicker();
      _cancelAutosaveTimers();
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

  Future<void> refreshAttemptStatus() async {
    if (_attempt == null) return;

    try {
      final response = await _attemptService.getAttemptStatus(attemptId: _attempt!.id);
      final data = response['data'] as Map<String, dynamic>? ?? response;
      final attemptJson = data['attempt'] as Map<String, dynamic>? ?? data;
      if (attemptJson.isNotEmpty) {
        final updated = QuizAttempt.fromJson(attemptJson);
        _applyAttemptUpdate(updated);
        notifyListeners();
      }
    } catch (_) {
      // Silent fail on background refresh
    }
  }

  void _applyAttemptUpdate(QuizAttempt updated) {
    _attempt = updated;
    _remainingSeconds = updated.remainingSeconds;
    if (isLocked) {
      _stopTicker();
    } else if (_remainingSeconds > 0 && _ticker == null) {
      _startTicker();
    }
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
        _selectedOptionIds[index] = saved.optionId!;
      }
      if (saved.optionIds.isNotEmpty) {
        _selectedOptionIdSets[index] = Set<int>.from(saved.optionIds);
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
    int? index = progress?.lastViewedQuestionIndex ?? _attempt?.lastViewedQuestionIndex;
    if (index == null) {
      final questionId = progress?.lastViewedQuestionId ?? _attempt?.lastViewedQuestionId;
      if (questionId != null) {
        final foundIndex = _questions.indexWhere((q) => q.id == questionId);
        if (foundIndex >= 0) {
          index = foundIndex;
        }
      }
    }
    if (index == null || index < 0 || index >= _questions.length) {
      _currentIndex = 0;
    } else {
      _currentIndex = index;
    }
  }

  void _autosaveProgress() {
    if (_attempt == null) return;
    if (isLocked) return;
    if (_questions.isEmpty) return;

    final question = currentQuestion;
    if (question == null) return;

    _queueAutosave(
      AutosavePayload(
        questionId: question.id,
        lastViewedQuestionId: question.id,
        lastViewedQuestionIndex: _currentIndex,
      ),
    );
  }

  void _queueAutosave(AutosavePayload payload) {
    if (_attempt == null) return;
    if (isLocked) return;

    _pendingAutosave = payload;
    _saveStatus = SaveStatus.saving;
    _autosaveTimer?.cancel();
    _autosaveTimer = Timer(_autosaveDebounce, () {
      _performAutosave(payload);
    });
    notifyListeners();
  }

  Future<void> _performAutosave(AutosavePayload payload) async {
    if (_attempt == null) return;
    if (isLocked) return;

    try {
      await _attemptService.saveAnswer(
        attemptId: _attempt!.id,
        questionId: payload.questionId,
        optionId: payload.optionId,
        optionIds: payload.optionIds,
        textAnswer: payload.textAnswer,
        answer: payload.answer,
        isBookmarked: payload.isBookmarked,
        lastViewedQuestionId: payload.lastViewedQuestionId,
        lastViewedQuestionIndex: payload.lastViewedQuestionIndex,
      );
      _saveStatus = SaveStatus.saved;
      _autosaveFailures = 0;
      _pendingAutosave = null;
    } catch (_) {
      _autosaveFailures += 1;
      _saveStatus = SaveStatus.retrying;
      _retryTimer?.cancel();
      _retryTimer = Timer(const Duration(seconds: 3), () {
        final retryPayload = _pendingAutosave ?? payload;
        _performAutosave(retryPayload);
      });
    } finally {
      notifyListeners();
    }
  }

  void _cancelAutosaveTimers() {
    _autosaveTimer?.cancel();
    _autosaveTimer = null;
    _retryTimer?.cancel();
    _retryTimer = null;
  }

  @override
  void dispose() {
    _stopTicker();
    _cancelAutosaveTimers();
    super.dispose();
  }
}

class AutosavePayload {
  final int questionId;
  final int? optionId;
  final List<int>? optionIds;
  final String? textAnswer;
  final String? answer;
  final bool? isBookmarked;
  final int? lastViewedQuestionId;
  final int? lastViewedQuestionIndex;

  const AutosavePayload({
    required this.questionId,
    this.optionId,
    this.optionIds,
    this.textAnswer,
    this.answer,
    this.isBookmarked,
    this.lastViewedQuestionId,
    this.lastViewedQuestionIndex,
  });
}
