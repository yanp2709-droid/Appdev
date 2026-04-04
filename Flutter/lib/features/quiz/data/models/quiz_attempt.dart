class QuizAttempt {
  final int id;
  final String status;
  final DateTime? startedAt;
  final DateTime? expiresAt;
  final DateTime? submittedAt;
  final int durationMinutes;
  final int remainingSeconds;
  final bool allowReviewBeforeSubmit;

  const QuizAttempt({
    required this.id,
    required this.status,
    required this.startedAt,
    required this.expiresAt,
    required this.submittedAt,
    required this.durationMinutes,
    required this.remainingSeconds,
    required this.allowReviewBeforeSubmit,
  });

  factory QuizAttempt.fromJson(Map<String, dynamic> json) {
    return QuizAttempt(
      id: (json['id'] as num?)?.toInt() ?? 0,
      status: json['status'] as String? ?? 'in_progress',
      startedAt: _parseDate(json['started_at']),
      expiresAt: _parseDate(json['expires_at']),
      submittedAt: _parseDate(json['submitted_at']),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      remainingSeconds: (json['remaining_seconds'] as num?)?.toInt() ?? 0,
      allowReviewBeforeSubmit:
          (json['allow_review_before_submit'] as bool?) ?? true,
    );
  }

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value.toLocal();
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value)?.toLocal();
    }
    return null;
  }
}
