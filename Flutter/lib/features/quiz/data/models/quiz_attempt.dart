class AttemptAvailability {
  final bool gradedAttemptAvailable;
  final bool practiceAttemptAvailable;
  final bool gradedAttemptUsed;
  final int remainingGradedAttempts;
  final int allowedGradedAttempts;
  final int submittedGradedAttempts;

  const AttemptAvailability({
    required this.gradedAttemptAvailable,
    required this.practiceAttemptAvailable,
    required this.gradedAttemptUsed,
    this.remainingGradedAttempts = 0,
    this.allowedGradedAttempts = 1,
    this.submittedGradedAttempts = 0,
  });

  factory AttemptAvailability.fromJson(Map<String, dynamic> json) {
    return AttemptAvailability(
      gradedAttemptAvailable: json['graded_attempt_available'] as bool? ?? true,
      practiceAttemptAvailable:
          json['practice_attempt_available'] as bool? ?? true,
      gradedAttemptUsed: json['graded_attempt_used'] as bool? ?? false,
      remainingGradedAttempts:
          (json['remaining_graded_attempts'] as num?)?.toInt() ?? 0,
      allowedGradedAttempts:
          (json['allowed_graded_attempts'] as num?)?.toInt() ?? 1,
      submittedGradedAttempts:
          (json['submitted_graded_attempts'] as num?)?.toInt() ?? 0,
    );
  }
}

class QuizAttempt {
  final int id;
  final String attemptType;
  final String status;
  final DateTime? startedAt;
  final DateTime? expiresAt;
  final DateTime? submittedAt;
  final int durationMinutes;
  final int remainingSeconds;
  final bool allowReviewBeforeSubmit;
  final String? schoolYear;


  const QuizAttempt({
    required this.id,
    required this.attemptType,
    required this.status,
    required this.startedAt,
    required this.expiresAt,
    required this.submittedAt,
    required this.durationMinutes,
    required this.remainingSeconds,
    required this.allowReviewBeforeSubmit,
    this.schoolYear,
  });


  factory QuizAttempt.fromJson(Map<String, dynamic> json) {
    return QuizAttempt(
      id: (json['id'] as num?)?.toInt() ?? 0,
      attemptType: json['attempt_type'] as String? ?? 'graded',
      status: json['status'] as String? ?? 'in_progress',
      startedAt: _parseDate(json['started_at']),
      expiresAt: _parseDate(json['expires_at']),
      submittedAt: _parseDate(json['submitted_at']),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      remainingSeconds: (json['remaining_seconds'] as num?)?.toInt() ?? 0,
      allowReviewBeforeSubmit:
          (json['allow_review_before_submit'] as bool?) ?? true,
      schoolYear: json['school_year'] as String?,
    );
  }


  bool get isGradedAttempt => attemptType == 'graded';
  bool get isPracticeAttempt => attemptType == 'practice';

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value.toLocal();
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value)?.toLocal();
    }
    return null;
  }
}
