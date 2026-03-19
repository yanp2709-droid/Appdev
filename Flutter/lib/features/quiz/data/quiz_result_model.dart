// lib/features/quiz/data/quiz_result_model.dart

class QuizResultModel {
  final int    attemptId;
  final int    categoryId;
  final String categoryName;
  final int    totalQuestions;
  final int    correctAnswers;
  final int?   scorePercentOverride;
  final DateTime submittedAt;

  const QuizResultModel({
    required this.attemptId,
    required this.categoryId,
    required this.categoryName,
    required this.totalQuestions,
    required this.correctAnswers,
    this.scorePercentOverride,
    required this.submittedAt,
  });

  int get scorePercent =>
      scorePercentOverride ??
      (totalQuestions == 0 ? 0 : ((correctAnswers / totalQuestions) * 100).round());
}
