import 'attempt.dart';

class QuizAttempt extends AttemptModel {
  const QuizAttempt({
    required super.id,
    required super.quizId,
    required super.categoryId,
    required super.categoryName,
    required super.status,
    required super.startedAt,
    required super.expiresAt,
    required super.submittedAt,
    required super.durationMinutes,
    required super.remainingSeconds,
    required super.totalItems,
    required super.answeredCount,
    required super.correctAnswers,
    required super.scorePercent,
  });

  factory QuizAttempt.fromJson(Map<String, dynamic> json) {
    return QuizAttempt(
      id: (json['id'] as num?)?.toInt() ?? 0,
      quizId: (json['quiz_id'] as num?)?.toInt() ?? 0,
      categoryId: (json['category_id'] as num?)?.toInt() ?? 0,
      categoryName: json['category_name'] as String? ?? '',
      status: json['status'] as String? ?? 'in_progress',
      startedAt: AttemptModel.tryParseDate(json['started_at']),
      expiresAt: AttemptModel.tryParseDate(json['expires_at']),
      submittedAt: AttemptModel.tryParseDate(json['submitted_at']),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      remainingSeconds: (json['remaining_seconds'] as num?)?.toInt() ?? 0,
      totalItems: (json['total_items'] as num?)?.toInt() ?? 0,
      answeredCount: (json['answered_count'] as num?)?.toInt() ?? 0,
      correctAnswers: (json['correct_answers'] as num?)?.toInt() ?? 0,
      scorePercent: (json['score_percent'] as num?)?.toDouble() ?? 0.0,
    );
  }
}
