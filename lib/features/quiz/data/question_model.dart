// lib/features/quiz/data/question_model.dart

class QuestionModel {
  final String id;
  final int    categoryId;
  final String questionText;
  final List<String> options;   // exactly 4 options
  final int    correctIndex;    // 0-3

  const QuestionModel({
    required this.id,
    required this.categoryId,
    required this.questionText,
    required this.options,
    required this.correctIndex,
  });

  factory QuestionModel.fromMap(Map<String, dynamic> map) {
    return QuestionModel(
      id:           (map['id'] as String?) ?? '',
      categoryId:   (map['category_id'] as int?) ?? 0,
      questionText: (map['question'] as String?) ?? '',
      options:      List<String>.from(map['options'] as List? ?? []),
      correctIndex: (map['correct_index'] as int?) ?? 0,
    );
  }

  Map<String, dynamic> toMap() => {
        'id':            id,
        'category_id':   categoryId,
        'question':      questionText,
        'options':       options,
        'correct_index': correctIndex,
      };
}
