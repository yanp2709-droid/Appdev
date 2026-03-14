import 'option_model.dart';

class QuestionModel {
  final int id;
  final String questionText;
  final List<OptionModel> options;
  final String questionType;
  final int quizId; // <-- add this field

  QuestionModel({
    required this.id,
    required this.questionText,
    required this.options,
    required this.questionType,
    required this.quizId, // <-- include in constructor
  });

  factory QuestionModel.fromJson(Map<String, dynamic> json) {
    return QuestionModel(
      id: json['id'],
      questionText: json['question_text'],
      options: (json['options'] as List)
          .map((e) => OptionModel.fromJson(e))
          .toList(),
      questionType: json['question_type'] ?? 'mcq',
      quizId: json['quiz_id'] ?? 0, // <-- set from API, default 0
    );
  }
}