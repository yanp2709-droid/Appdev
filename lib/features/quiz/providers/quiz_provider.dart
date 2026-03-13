// lib/features/quiz/providers/quiz_provider.dart
import 'package:flutter/foundation.dart';
import '../data/question_model.dart';
import '../data/quiz_result_model.dart';
import '../data/questions_repository.dart';

enum QuizStatus { idle, loading, active, finished, error }

class QuizProvider extends ChangeNotifier {
  final QuestionsRepository _repo;

  QuizProvider(this._repo);

  QuizStatus            _status       = QuizStatus.idle;
  List<QuestionModel>   _questions    = [];
  int                   _currentIndex = 0;
  final Map<int, int>   _answers      = {}; // questionIndex -> chosen option
  int                   _categoryId   = 0;
  String                _categoryName = '';
  QuizResultModel?      _lastResult;
  String?               _errorMessage;

  QuizStatus            get status        => _status;
  List<QuestionModel>   get questions     => _questions;
  int                   get currentIndex  => _currentIndex;
  QuestionModel?        get currentQuestion =>
      _questions.isEmpty ? null : _questions[_currentIndex];
  int                   get totalQuestions => _questions.length;
  bool                  get isLastQuestion => _currentIndex == _questions.length - 1;
  Map<int, int>         get answers       => Map.unmodifiable(_answers);
  QuizResultModel?      get lastResult    => _lastResult;
  String?               get errorMessage  => _errorMessage;
  bool                  get hasEverTakenQuiz => _lastResult != null;

  /// Fetches questions from API asynchronously
  Future<void> startQuiz(int categoryId, String categoryName) async {
    _status = QuizStatus.loading;
    _errorMessage = null;
    _categoryId = categoryId;
    _categoryName = categoryName;
    notifyListeners();

    try {
      _questions = await _repo.getByCategory(categoryId);
      _currentIndex = 0;
      _answers.clear();

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

  /// Student selects an answer for the current question
  void answerQuestion(int optionIndex) {
    _answers[_currentIndex] = optionIndex;
    notifyListeners();
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
    int correct = 0;
    for (int i = 0; i < _questions.length; i++) {
      if (_answers[i] == _questions[i].correctIndex) correct++;
    }
    _lastResult = QuizResultModel(
      categoryId:     _categoryId,
      categoryName:   _categoryName,
      totalQuestions: _questions.length,
      correctAnswers: correct,
      takenAt:        DateTime.now(),
    );
    _status = QuizStatus.finished;
  }

  void reset() {
    _status       = QuizStatus.idle;
    _currentIndex = 0;
    _answers.clear();
    notifyListeners();
  }
}