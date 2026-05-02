/// Quiz model representing a quiz that belongs to a Subject
class QuizModel {
  final int id;
  final int subjectId;
  final String title;
  final String? description;
  final bool isActive;
  final int questionCount;
  final int timeLimit; // in minutes
  final DateTime? createdAt;
  final DateTime? updatedAt;

  const QuizModel({
    required this.id,
    required this.subjectId,
    required this.title,
    this.description,
    this.isActive = true,
    this.questionCount = 0,
    this.timeLimit = 15,
    this.createdAt,
    this.updatedAt,
  });

  /// Create QuizModel from JSON API response
  factory QuizModel.fromJson(Map<String, dynamic> json) {
    return QuizModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      subjectId: (json['subject_id'] as num?)?.toInt() ?? 0,
      title: json['title'] as String? ?? '',
      description: json['description'] as String?,
      isActive: json['is_active'] as bool? ?? json['is_active'] != false,
      questionCount: (json['questions_count'] as num?)?.toInt() ??
                   (json['question_count'] as num?)?.toInt() ?? 0,
      timeLimit: (json['time_limit'] as num?)?.toInt() ??
                (json['duration_minutes'] as num?)?.toInt() ?? 15,
      createdAt: _parseDate(json['created_at']),
      updatedAt: _parseDate(json['updated_at']),
    );
  }

  /// Convert to JSON
  Map<String, dynamic> toJson() => {
    'id': id,
    'subject_id': subjectId,
    'title': title,
    'description': description,
    'is_active': isActive,
    'questions_count': questionCount,
    'time_limit': timeLimit,
    'created_at': createdAt?.toIso8601String(),
    'updated_at': updatedAt?.toIso8601String(),
  };

  /// Check if quiz is available for taking
  bool get isAvailable => isActive;

  /// Get time limit display string
  String get timeLimitDisplay => '$timeLimit min';

  /// Get question count display string
  String get questionCountDisplay => '$questionCount questions';

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value.toLocal();
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value)?.toLocal();
    }
    return null;
  }

  @override
  String toString() => 'QuizModel(id: $id, title: $title, subjectId: $subjectId)';
}
