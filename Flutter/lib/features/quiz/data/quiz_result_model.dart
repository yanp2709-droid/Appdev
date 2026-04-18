// lib/features/quiz/data/quiz_result_model.dart

class QuizResultModel {
  final int    categoryId;
  final String categoryName;
  final int    totalQuestions;
  final int    correctAnswers;
  final String attemptType;
  final bool   isOfficialGradedAttempt;
  final int?   scorePercentOverride;
  final DateTime takenAt;

  const QuizResultModel({
    required this.categoryId,
    required this.categoryName,
    required this.totalQuestions,
    required this.correctAnswers,
    required this.attemptType,
    required this.isOfficialGradedAttempt,
    this.scorePercentOverride,
    required this.takenAt,
  });

  int get scorePercent =>
      scorePercentOverride ??
      (totalQuestions == 0 ? 0 : ((correctAnswers / totalQuestions) * 100).round());

  bool get isPracticeAttempt => attemptType == 'practice';
}
