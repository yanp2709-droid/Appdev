import 'dart:async';
import 'package:flutter/foundation.dart';
import '../data/models/question.dart';
import '../data/quiz_result_model.dart';
import '../../../services/quiz_service.dart';

enum QuizStatus { idle, loading, active, finished, error }

class QuizProvider extends ChangeNotifier {
  final QuizService _quizService;

  QuizProvider(this._quizService);

  // ── State ─────────────────────────────
  QuizStatus _status = QuizStatus.idle;
  List<QuestionModel> _questions = [];
  int _currentIndex = 0;
  final Map<int, int> _selectedOptionIds = {}; // questionIndex -> optionId
  final Map<int, String> _textAnswers = {}; // questionIndex -> text
  int _categoryId = 0;
  String _categoryName = '';
  QuizResultModel? _lastResult;
  String? _errorMessage;
  Timer? _ticker;
  int _remainingSeconds = 0;

  // ── Getters ───────────────────────────
  QuizStatus get status => _status;
  List<QuestionModel> get questions => _questions;
  int get currentIndex => _currentIndex;
  QuestionModel? get currentQuestion =>
      _questions.isEmpty ? null : _questions[_currentIndex];
  int get totalQuestions => _questions.length;
  bool get isLastQuestion => _currentIndex == _questions.length - 1;
  Map<int, int> get answers => Map.unmodifiable(_selectedOptionIds);
  Map<int, String> get textAnswers => Map.unmodifiable(_textAnswers);
  QuizResultModel? get lastResult => _lastResult;
  String? get errorMessage => _errorMessage;
  bool get hasEverTakenQuiz => _lastResult != null;
  int get remainingSeconds => _remainingSeconds;
  bool get isExpired => _remainingSeconds <= 0;

  String get timerLabel {
    final secs = _remainingSeconds < 0 ? 0 : _remainingSeconds;
    final minutes = secs ~/ 60;
    final seconds = secs % 60;
    return '${minutes.toString().padLeft(2, '0')}:${seconds.toString().padLeft(2, '0')}';
  }

  // ── Start Quiz ─────────────────────────
  Future<void> startQuiz(int categoryId, String categoryName) async {
    _status = QuizStatus.loading;
    _errorMessage = null;
    _categoryId = categoryId;
    _categoryName = categoryName;
    notifyListeners();

    try {
      final response = await _quizService.getQuizzesByCategory(categoryId);
      if (response.isEmpty) {
        _status = QuizStatus.error;
        _errorMessage = 'No questions found for this category';
        notifyListeners();
        return;
      }

      _questions =
          response.map<QuestionModel>((q) => QuestionModel.fromJson(q)).toList();

      _currentIndex = 0;
      _selectedOptionIds.clear();
      _textAnswers.clear();
      _remainingSeconds = 600; // default 10 min, can be dynamic
      _startTicker();

      _status = QuizStatus.active;
    } catch (e) {
      _status = QuizStatus.error;
      _errorMessage = 'Failed to load quiz: $e';
    }
    notifyListeners();
  }

  // ── Answer Questions ───────────────────
  void answerQuestion(int optionIndex) {
    if (currentQuestion == null || isExpired) return;
    final question = currentQuestion!;
    final optionId = question.options[optionIndex].id;
    _selectedOptionIds[_currentIndex] = optionId;
    notifyListeners();
  }

  void answerText(String text) {
    if (currentQuestion == null || isExpired) return;
    _textAnswers[_currentIndex] = text;
    notifyListeners();
  }

  // ── Navigation ────────────────────────
  void nextQuestion() {
    if (_currentIndex < _questions.length - 1) {
      _currentIndex++;
    } else {
      _finishQuiz();
    }
    notifyListeners();
  }

  void previousQuestion() {
    if (_currentIndex > 0) _currentIndex--;
    notifyListeners();
  }

  void _finishQuiz() {
    _status = QuizStatus.finished;
    _stopTicker();
  }

  void reset() {
    _status = QuizStatus.idle;
    _currentIndex = 0;
    _selectedOptionIds.clear();
    _textAnswers.clear();
    _lastResult = null;
    _remainingSeconds = 0;
    _stopTicker();
    notifyListeners();
  }

  // ── Submit Attempt ────────────────────
  Future<void> submitAttempt() async {
    if (_questions.isEmpty) return;
    try {
      final response = await _quizService.submitQuizAttempt(
        quizId: _categoryId, // we use categoryId as quizId placeholder
        answers: {
          ..._selectedOptionIds.map((k, v) => MapEntry(k.toString(), v)),
          ..._textAnswers.map((k, v) => MapEntry(k.toString(), v)),
        },
      );

      final data = response['data'] as Map<String, dynamic>? ?? {};
      final score = data['score'] as Map<String, dynamic>? ?? {};

      _lastResult = QuizResultModel(
        categoryId: _categoryId,
        categoryName: _categoryName,
        totalQuestions: score['total_items'] as int? ?? _questions.length,
        correctAnswers: score['correct_answers'] as int? ?? 0,
        scorePercentOverride: score['score_percent'] as int? ?? 0,
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

  // ── Timer ─────────────────────────────
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