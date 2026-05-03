class AttemptHistoryModel {
  final int id;
  final int quizId;
  final int categoryId;
  final String categoryName;
  final String attemptType;
  final bool isOfficialGradedAttempt;
  final bool isPracticeAttempt;
  final String status;
  final DateTime? startedAt;
  final DateTime? submittedAt;
  final int durationMinutes;
  final int totalItems;
  final int answeredCount;
  final int? correctAnswers;
  final double? scorePercent;
  final String? schoolYear;


  const AttemptHistoryModel({
    required this.id,
    required this.quizId,
    required this.categoryId,
    required this.categoryName,
    required this.attemptType,
    required this.isOfficialGradedAttempt,
    required this.isPracticeAttempt,
    required this.status,
    required this.startedAt,
    required this.submittedAt,
    required this.durationMinutes,
    required this.totalItems,
    required this.answeredCount,
    required this.correctAnswers,
    required this.scorePercent,
    this.schoolYear,
  });


  factory AttemptHistoryModel.fromJson(Map<String, dynamic> json) {
    return AttemptHistoryModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      quizId: (json['quiz_id'] as num?)?.toInt() ?? 0,
      categoryId: (json['category_id'] as num?)?.toInt() ?? 0,
      categoryName: json['category_name'] as String? ?? '',
      attemptType: json['attempt_type'] as String? ?? 'graded',
      isOfficialGradedAttempt:
          json['is_official_graded_attempt'] as bool? ??
              json['is_scored_attempt'] as bool? ??
              false,
      isPracticeAttempt: json['is_practice_attempt'] as bool? ?? false,
      status: json['status'] as String? ?? '',
      startedAt: _parseDate(json['started_at']),
      submittedAt: _parseDate(json['submitted_at']),
      durationMinutes: (json['duration_minutes'] as num?)?.toInt() ?? 0,
      totalItems: (json['total_items'] as num?)?.toInt() ?? 0,
      answeredCount: (json['answered_count'] as num?)?.toInt() ?? 0,
      correctAnswers: (json['correct_answers'] as num?)?.toInt(),
      scorePercent: (json['score_percent'] as num?)?.toDouble(),
      schoolYear: json['school_year'] as String?,
    );
  }


  Map<String, dynamic> toJson() => {
        'id': id,
        'quiz_id': quizId,
        'category_id': categoryId,
        'category_name': categoryName,
        'attempt_type': attemptType,
        'is_official_graded_attempt': isOfficialGradedAttempt,
        'is_practice_attempt': isPracticeAttempt,
        'status': status,
        'started_at': startedAt?.toIso8601String(),
        'submitted_at': submittedAt?.toIso8601String(),
        'duration_minutes': durationMinutes,
        'total_items': totalItems,
        'answered_count': answeredCount,
        'correct_answers': correctAnswers,
        'score_percent': scorePercent,
        if (schoolYear != null) 'school_year': schoolYear,
      };


  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value.toLocal();
    if (value is String) {
      try {
        return DateTime.parse(value).toLocal();
      } catch (_) {
        return null;
      }
    }
    return null;
  }
}
