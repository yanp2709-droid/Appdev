// lib/features/quiz/data/questions_repository.dart
import 'question_model.dart';

/// In-memory question store shared across the app session.
/// Admin adds questions; students read them during quiz.
class QuestionsRepository {
  // Singleton so admin writes and student reads the same data
  static final QuestionsRepository _instance = QuestionsRepository._internal();
  factory QuestionsRepository() => _instance;
  QuestionsRepository._internal();

  final List<QuestionModel> _questions = [
    // ── Seed data ──────────────────────────────────────────────────────────
    QuestionModel(
      id: '1', categoryId: 1,
      questionText: 'What is 12 × 12?',
      options: ['124', '144', '132', '152'],
      correctIndex: 1,
    ),
    QuestionModel(
      id: '2', categoryId: 1,
      questionText: 'What is the square root of 81?',
      options: ['7', '8', '9', '10'],
      correctIndex: 2,
    ),
    QuestionModel(
      id: '3', categoryId: 1,
      questionText: 'What is 15% of 200?',
      options: ['25', '30', '35', '20'],
      correctIndex: 1,
    ),
    QuestionModel(
      id: '4', categoryId: 2,
      questionText: 'What is the chemical symbol for water?',
      options: ['O2', 'H2O', 'CO2', 'HO'],
      correctIndex: 1,
    ),
    QuestionModel(
      id: '5', categoryId: 2,
      questionText: 'How many bones are in the adult human body?',
      options: ['196', '206', '216', '186'],
      correctIndex: 1,
    ),
    QuestionModel(
      id: '6', categoryId: 3,
      questionText: 'In what year did World War II end?',
      options: ['1943', '1944', '1945', '1946'],
      correctIndex: 2,
    ),
    QuestionModel(
      id: '7', categoryId: 3,
      questionText: 'Who was the first President of the United States?',
      options: ['Abraham Lincoln', 'Thomas Jefferson', 'George Washington', 'John Adams'],
      correctIndex: 2,
    ),
    QuestionModel(
      id: '8', categoryId: 4,
      questionText: 'What does CPU stand for?',
      options: ['Central Processing Unit', 'Computer Personal Unit', 'Central Program Utility', 'Core Processing Unit'],
      correctIndex: 0,
    ),
    QuestionModel(
      id: '9', categoryId: 4,
      questionText: 'Which language is Flutter built with?',
      options: ['Kotlin', 'Swift', 'Dart', 'Java'],
      correctIndex: 2,
    ),
  ];

  List<QuestionModel> getByCategory(int categoryId) =>
      _questions.where((q) => q.categoryId == categoryId).toList();

  List<QuestionModel> getAll() => List.unmodifiable(_questions);

  void addQuestion(QuestionModel q) {
    _questions.add(q);
  }

  void deleteQuestion(String id) {
    _questions.removeWhere((q) => q.id == id);
  }

  void updateQuestion(QuestionModel updated) {
    final idx = _questions.indexWhere((q) => q.id == updated.id);
    if (idx != -1) _questions[idx] = updated;
  }
}
