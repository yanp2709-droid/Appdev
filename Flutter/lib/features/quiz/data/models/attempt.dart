class AttemptModel {
  final int id;
  final int quizId;
  final int categoryId;
  final String categoryName;
  final String status;
  final DateTime? startedAt;
  final DateTime? expiresAt;
  final DateTime? submittedAt;
  final int durationMinutes;
  final int remainingSeconds;
  final int totalItems;
  final int answeredCount;
  final int correctAnswers;
  final double scorePercent;

  const AttemptModel({
    required this.id,
    required this.quizId,
    required this.categoryId,
    required this.categoryName,
    required this.status,
    required this.startedAt,
    required this.expiresAt,
    required this.submittedAt,
    required this.durationMinutes,
    required this.remainingSeconds,
    required this.totalItems,
    required this.answeredCount,
    required this.correctAnswers,
    required this.scorePercent,
  });

  static DateTime? tryParseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value);
    }
    return null;
  }
}
