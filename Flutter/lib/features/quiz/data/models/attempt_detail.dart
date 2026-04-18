class AttemptOption {
  final int id;
  final String text;
  final bool isSelected;
  final bool? isCorrect;
  final int? orderIndex;

  const AttemptOption({
    required this.id,
    required this.text,
    required this.isSelected,
    this.isCorrect,
    this.orderIndex,
  });

  factory AttemptOption.fromJson(Map<String, dynamic> json) {
    return AttemptOption(
      id: (json['id'] as num?)?.toInt() ?? 0,
      text: json['text'] as String? ?? '',
      isSelected: json['is_selected'] as bool? ?? false,
      isCorrect: json['is_correct'] as bool?,
      orderIndex: (json['order_index'] as num?)?.toInt(),
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'text': text,
        'is_selected': isSelected,
        'is_correct': isCorrect,
        'order_index': orderIndex,
      };
}

class AttemptQuestionDetail {
  final int questionId;
  final String questionText;
  final String questionType;
  final int points;
  final List<AttemptOption> options;
  final int? selectedOptionId;
  final int? correctOptionId;
  final String? textAnswer;
  final bool isAnswered;
  final bool? isCorrect;
  final int? answerId;

  const AttemptQuestionDetail({
    required this.questionId,
    required this.questionText,
    required this.questionType,
    required this.points,
    required this.options,
    this.selectedOptionId,
    this.correctOptionId,
    this.textAnswer,
    required this.isAnswered,
    this.isCorrect,
    this.answerId,
  });

  factory AttemptQuestionDetail.fromJson(Map<String, dynamic> json) {
    final optionsList = (json['options'] as List<dynamic>? ?? [])
        .map((o) => AttemptOption.fromJson(o as Map<String, dynamic>))
        .toList();

    return AttemptQuestionDetail(
      questionId: (json['question_id'] as num?)?.toInt() ?? 0,
      questionText: json['question_text'] as String? ?? '',
      questionType: json['question_type'] as String? ?? '',
      points: (json['points'] as num?)?.toInt() ?? 0,
      options: optionsList,
      selectedOptionId: (json['selected_option_id'] as num?)?.toInt(),
      correctOptionId: (json['correct_option_id'] as num?)?.toInt(),
      textAnswer: json['text_answer'] as String?,
      isAnswered: json['is_answered'] as bool? ?? false,
      isCorrect: json['is_correct'] as bool?,
      answerId: (json['answer_id'] as num?)?.toInt(),
    );
  }

  Map<String, dynamic> toJson() => {
        'question_id': questionId,
        'question_text': questionText,
        'question_type': questionType,
        'points': points,
        'options': options.map((o) => o.toJson()).toList(),
        'selected_option_id': selectedOptionId,
        'correct_option_id': correctOptionId,
        'text_answer': textAnswer,
        'is_answered': isAnswered,
        'is_correct': isCorrect,
        'answer_id': answerId,
      };
}

class AttemptDetailModel {
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
  final int totalItems;
  final int answeredCount;
  final int? correctAnswers;
  final double? scorePercent;
  final List<AttemptQuestionDetail> questions;

  const AttemptDetailModel({
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
    required this.totalItems,
    required this.answeredCount,
    required this.correctAnswers,
    required this.scorePercent,
    required this.questions,
  });

  factory AttemptDetailModel.fromJson(Map<String, dynamic> json) {
    final attemptData = json['attempt'] as Map<String, dynamic>? ?? {};
    final questionsList = (json['questions'] as List<dynamic>? ?? [])
        .map((q) => AttemptQuestionDetail.fromJson(q as Map<String, dynamic>))
        .toList();

    return AttemptDetailModel(
      id: (attemptData['id'] as num?)?.toInt() ?? 0,
      quizId: (attemptData['quiz_id'] as num?)?.toInt() ?? 0,
      categoryId: (attemptData['category_id'] as num?)?.toInt() ?? 0,
      categoryName: attemptData['category_name'] as String? ?? '',
      attemptType: attemptData['attempt_type'] as String? ?? 'graded',
      isOfficialGradedAttempt:
          attemptData['is_official_graded_attempt'] as bool? ??
              attemptData['is_scored_attempt'] as bool? ??
              false,
      isPracticeAttempt: attemptData['is_practice_attempt'] as bool? ?? false,
      status: attemptData['status'] as String? ?? '',
      startedAt: _parseDate(attemptData['started_at']),
      submittedAt: _parseDate(attemptData['submitted_at']),
      totalItems: (attemptData['total_items'] as num?)?.toInt() ?? 0,
      answeredCount: (attemptData['answered_count'] as num?)?.toInt() ?? 0,
      correctAnswers: (attemptData['correct_answers'] as num?)?.toInt(),
      scorePercent: (attemptData['score_percent'] as num?)?.toDouble(),
      questions: questionsList,
    );
  }

  Map<String, dynamic> toJson() => {
        'attempt': {
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
          'total_items': totalItems,
          'answered_count': answeredCount,
          'correct_answers': correctAnswers,
          'score_percent': scorePercent,
        },
        'questions': questions.map((q) => q.toJson()).toList(),
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
