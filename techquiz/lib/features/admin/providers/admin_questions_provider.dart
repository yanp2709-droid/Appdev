// lib/features/admin/providers/admin_questions_provider.dart
import 'package:flutter/foundation.dart';
import '../../quiz/data/question_model.dart';
import '../../quiz/data/questions_repository.dart';

class AdminQuestionsProvider extends ChangeNotifier {
  final QuestionsRepository _repo;

  AdminQuestionsProvider(this._repo);

  List<QuestionModel> get allQuestions => _repo.getAll();

  List<QuestionModel> getByCategory(int categoryId) =>
      _repo.getByCategory(categoryId);

  void addQuestion(QuestionModel q) {
    _repo.addQuestion(q);
    notifyListeners();
  }

  void deleteQuestion(String id) {
    _repo.deleteQuestion(id);
    notifyListeners();
  }

  void updateQuestion(QuestionModel updated) {
    _repo.updateQuestion(updated);
    notifyListeners();
  }
}
