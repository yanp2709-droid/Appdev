import 'option.dart';

class QuestionModel {
  final int id;
  final int categoryId;        // <-- Add this
  final String questionText;
  final String questionType;   // e.g., 'mcq' or 'short_answer'
  final List<OptionModel> options;

  QuestionModel({
    required this.id,
    required this.categoryId,    // <-- Include in constructor
    required this.questionText,
    required this.questionType,
    required this.options,
  });

  factory QuestionModel.fromJson(Map<String, dynamic> json) {
    return QuestionModel(
      id: json['id'],
      categoryId: json['category_id'] ?? 0,  // <-- Parse categoryId from JSON
      questionText: json['question_text'],
      questionType: json['question_type'] ?? 'mcq',
      options: (json['options'] as List<dynamic>)
          .map((e) => OptionModel.fromJson(e))
          .toList(),
    );
  }
}