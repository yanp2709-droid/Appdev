import 'package:flutter/foundation.dart';
import '../data/models/quiz.dart';
import '../../../services/quiz_list_service.dart';

/// Status enum for quiz list operations
enum QuizzesStatus { initial, loading, success, error, empty }

/// Provider for managing quiz list state
/// Handles fetching quizzes for a specific subject
class QuizListProvider extends ChangeNotifier {
  final QuizListService _quizListService;

  QuizListProvider({QuizListService? quizListService})
      : _quizListService = quizListService ?? QuizListService();

  QuizzesStatus _status = QuizzesStatus.initial;
  List<QuizModel> _quizzes = [];
  QuizModel? _selectedQuiz;
  String? _errorMessage;
  int? _currentSubjectId;
  String? _currentSubjectName;

  /// Getters
  QuizzesStatus get status => _status;
  List<QuizModel> get quizzes => List.unmodifiable(_quizzes);
  QuizModel? get selectedQuiz => _selectedQuiz;
  String? get errorMessage => _errorMessage;
  int? get currentSubjectId => _currentSubjectId;
  String? get currentSubjectName => _currentSubjectName;

  /// Filtered lists
  List<QuizModel> get activeQuizzes => _quizzes.where((q) => q.isActive).toList();
  List<QuizModel> get inactiveQuizzes => _quizzes.where((q) => !q.isActive).toList();

  /// Check if any quizzes available
  bool get hasQuizzes => _quizzes.isNotEmpty;
  bool get hasActiveQuizzes => activeQuizzes.isNotEmpty;

  /// Fetch quizzes for a specific subject
  Future<void> fetchQuizzes(int subjectId, {String? subjectName}) async {
    _status = QuizzesStatus.loading;
    _errorMessage = null;
    _currentSubjectId = subjectId;
    _currentSubjectName = subjectName;
    _selectedQuiz = null;
    notifyListeners();

    try {
      final data = await _quizListService.getQuizzesBySubject(subjectId);
      _quizzes = data;
      final visibleQuizzes = data.where((quiz) => quiz.isActive).toList();
      _status = visibleQuizzes.isEmpty
          ? QuizzesStatus.empty
          : QuizzesStatus.success;
    } catch (e) {
      _errorMessage = e.toString();
      _status = QuizzesStatus.error;
    }
    notifyListeners();
  }

  /// Select a quiz for taking
  void selectQuiz(QuizModel quiz) {
    _selectedQuiz = quiz;
    notifyListeners();
  }

  /// Clear selected quiz
  void clearSelection() {
    _selectedQuiz = null;
    notifyListeners();
  }

  /// Clear all data (reset provider)
  void reset() {
    _status = QuizzesStatus.initial;
    _quizzes = [];
    _selectedQuiz = null;
    _errorMessage = null;
    _currentSubjectId = null;
    _currentSubjectName = null;
    notifyListeners();
  }

  /// Refresh quizzes for current subject
  Future<void> refresh() async {
    if (_currentSubjectId != null) {
      await fetchQuizzes(_currentSubjectId!, subjectName: _currentSubjectName);
    }
  }
}
