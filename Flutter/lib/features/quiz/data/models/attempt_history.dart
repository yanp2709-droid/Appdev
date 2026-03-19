import 'attempt.dart';

class AttemptHistoryModel extends AttemptModel {
  const AttemptHistoryModel({
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

  factory AttemptHistoryModel.fromJson(Map<String, dynamic> json) {
    return AttemptHistoryModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      quizId: (json['quiz_id'] as num?)?.toInt() ?? 0,
      categoryId: (json['category_id'] as num?)?.toInt() ?? 0,
      categoryName: json['category_name'] as String? ?? '',
      status: json['status'] as String? ?? '',
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

  Map<String, dynamic> toJson() => {
    'id': id,
    'quiz_id': quizId,
    'category_id': categoryId,
    'category_name': categoryName,
    'status': status,
    'started_at': startedAt?.toIso8601String(),
    'expires_at': expiresAt?.toIso8601String(),
    'submitted_at': submittedAt?.toIso8601String(),
    'duration_minutes': durationMinutes,
    'remaining_seconds': remainingSeconds,
    'total_items': totalItems,
    'answered_count': answeredCount,
    'correct_answers': correctAnswers,
    'score_percent': scorePercent,
  };
}
