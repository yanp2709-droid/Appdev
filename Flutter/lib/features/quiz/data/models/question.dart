class QuestionOptionModel {
  final int id;
  final String optionText;

  const QuestionOptionModel({
    required this.id,
    required this.optionText,
  });

  factory QuestionOptionModel.fromJson(Map<String, dynamic> json) {
    return QuestionOptionModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      optionText: json['option_text'] as String? ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'option_text': optionText,
  };
}

class QuestionModel {
  final int id;
  final int categoryId;
  final int quizId;
  final String questionType;
  final String questionText;
  final int points;
  final List<QuestionOptionModel> options;
  final String? academicYear;


  const QuestionModel({
    required this.id,
    required this.categoryId,
    required this.quizId,
    required this.questionType,
    required this.questionText,
    required this.points,
    required this.options,
    this.academicYear,
  });


  factory QuestionModel.fromJson(Map<String, dynamic> json) {
    final optionsList = (json['options'] as List<dynamic>? ?? [])
        .map((o) => QuestionOptionModel.fromJson(o as Map<String, dynamic>))
        .toList();

    return QuestionModel(
      id: (json['id'] as num?)?.toInt() ?? 0,
      categoryId: (json['category_id'] as num?)?.toInt() ?? 0,
      quizId: (json['quiz_id'] as num?)?.toInt() ?? 0,
      questionType: json['question_type'] as String? ?? 'mcq',
      questionText: json['question_text'] as String? ?? '',
      points: (json['points'] as num?)?.toInt() ?? 1,
      options: optionsList,
      academicYear: json['academic_year'] as String?,
    );

  }

  Map<String, dynamic> toJson() => {
    'id': id,
    'category_id': categoryId,
    'quiz_id': quizId,
    'question_type': questionType,
    'question_text': questionText,
    'points': points,
    'options': options.map((o) => o.toJson()).toList(),
    if (academicYear != null) 'academic_year': academicYear,
  };


  @override
  String toString() => 'QuestionModel(id: $id, type: $questionType)';
}
