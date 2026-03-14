// lib/features/quiz/providers/quiz_provider.dart
import 'dart:async';
import 'package:flutter/foundation.dart';
import '../data/models/question.dart';
import '../data/models/quiz_attempt.dart';
import '../data/quiz_result_model.dart';
import '../../../services/quiz_attempt_service.dart';

enum QuizStatus { idle, loading, active, finished, error }

class QuizProvider extends ChangeNotifier {
  final QuizAttemptService _attemptService;

  QuizProvider(this._attemptService);

  QuizStatus            _status       = QuizStatus.idle;
  List<QuestionModel>   _questions    = [];
  int                   _currentIndex = 0;
  final Map<int, int>   _selectedOptionIds = {}; // questionIndex -> optionId
  final Map<int, String> _textAnswers = {}; // questionIndex -> text
  int                   _categoryId   = 0;
  String                _categoryName = '';
  QuizResultModel?      _lastResult;
  String?               _errorMessage;
  QuizAttempt?          _attempt;
  Timer?                _ticker;
  int                   _remainingSeconds = 0;

  QuizStatus            get status        => _status;
  List<QuestionModel>   get questions     => _questions;
  int                   get currentIndex  => _currentIndex;
  QuestionModel?        get currentQuestion =>
      _questions.isEmpty ? null : _questions[_currentIndex];
  int                   get totalQuestions => _questions.length;
  bool                  get isLastQuestion => _currentIndex == _questions.length - 1;
  Map<int, int>         get answers       => Map.unmodifiable(_selectedOptionIds);
  Map<int, String>      get textAnswers   => Map.unmodifiable(_textAnswers);
  QuizResultModel?      get lastResult    => _lastResult;
  String?               get errorMessage  => _errorMessage;
  bool                  get hasEverTakenQuiz => _lastResult != null;
  int                   get remainingSeconds => _remainingSeconds;
  QuizAttempt?          get attempt => _attempt;
  bool                  get isExpired => _remainingSeconds <= 0;

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

      _remainingSeconds = _attempt?.remainingSeconds ?? 0;
      _startTicker();

      if (_questions.isEmpty) {
        _status = QuizStatus.error;
        _errorMessage = 'No questions found for this category';
      } else {
        _status = QuizStatus.active;
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
    _selectedOptionIds[_currentIndex] = optionId;
    notifyListeners();

    await _attemptService.saveAnswer(
      attemptId: _attempt!.id,
      questionId: question.id,
      optionId: optionId,
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

  void reset() {
    _status       = QuizStatus.idle;
    _currentIndex = 0;
    _selectedOptionIds.clear();
    _textAnswers.clear();
    _attempt = null;
    _remainingSeconds = 0;
    _stopTicker();
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
      final percent = score['score_percent'] as int? ?? 0;

      _lastResult = QuizResultModel(
        categoryId:     _categoryId,
        categoryName:   _categoryName,
        totalQuestions: totalItems,
        correctAnswers: correct,
        scorePercentOverride:   percent,
        takenAt:        DateTime.now(),
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
}
