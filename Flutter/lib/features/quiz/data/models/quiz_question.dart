// lib/features/quiz/data/models/quiz_question.dart

class QuizQuestion {
  final String id;
  final String questionText;
  final List<String> options;
  final String correctAnswer;

  const QuizQuestion({
    required this.id,
    required this.questionText,
    required this.options,
    required this.correctAnswer,
  });

  factory QuizQuestion.fromJson(Map<String, dynamic> json) {
    return QuizQuestion(
      id: json['id']?.toString() ?? '',
      questionText: json['question'] ?? '',
      options: List<String>.from(json['options'] ?? []),
      correctAnswer: json['correct_answer'] ?? '',
    );
  }

  Map<String, dynamic> toJson() => {
        'id': id,
        'question': questionText,
        'options': options,
        'correct_answer': correctAnswer,
      };
}