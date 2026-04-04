class AttemptSavedAnswer {
  final int questionId;
  final int? optionId;
  final String? textAnswer;
  final String? answer;
  final bool? isBookmarked;

  const AttemptSavedAnswer({
    required this.questionId,
    this.optionId,
    this.textAnswer,
    this.answer,
    this.isBookmarked,
  });

  factory AttemptSavedAnswer.fromJson(Map<String, dynamic> json) {
    return AttemptSavedAnswer(
      questionId: (json['question_id'] as num?)?.toInt() ?? 0,
      optionId: (json['option_id'] as num?)?.toInt(),
      textAnswer: json['text_answer'] as String?,
      answer: json['answer'] as String?,
      isBookmarked: json['is_bookmarked'] as bool?,
    );
  }
}

class AttemptProgress {
  final int? lastViewedQuestionId;
  final int? lastViewedQuestionIndex;
  final DateTime? lastActivityAt;

  const AttemptProgress({
    this.lastViewedQuestionId,
    this.lastViewedQuestionIndex,
    this.lastActivityAt,
  });

  factory AttemptProgress.fromJson(Map<String, dynamic> json) {
    return AttemptProgress(
      lastViewedQuestionId: (json['last_viewed_question_id'] as num?)?.toInt(),
      lastViewedQuestionIndex: (json['last_viewed_question_index'] as num?)?.toInt(),
      lastActivityAt: _parseDate(json['last_activity_at']),
    );
  }

  static DateTime? _parseDate(dynamic value) {
    if (value == null) return null;
    if (value is DateTime) return value;
    if (value is String && value.isNotEmpty) {
      return DateTime.tryParse(value);
    }
    return null;
  }
}
