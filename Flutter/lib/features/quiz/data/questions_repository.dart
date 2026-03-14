// lib/features/quiz/data/questions_repository.dart
import '../../../services/questions_service.dart';
import 'question_model.dart';

/// Questions repository that fetches from Laravel API
/// Converts database question models to the app's question model
class QuestionsRepository {
  final QuestionsService _questionsService = QuestionsService();
  
  // Cache for session
  final Map<int, List<QuestionModel>> _cache = {};

  /// Get questions for a specific category (database only)
  /// Fetches from API if not cached
  Future<List<QuestionModel>> getByCategory(
    int categoryId, {
    bool useCache = true,
    int limit = 10,
    bool random = false,
  }) async {
    // Return cached data if available and cache is enabled
    if (useCache && _cache.containsKey(categoryId)) {
      return _cache[categoryId]!;
    }

    // Fetch from API only (no fallback to mock data)
    final apiQuestions = await _questionsService.getQuestionsByCategory(
      categoryId: categoryId,
      limit: limit,
      random: random,
    );

    // Convert to app's QuestionModel format
    final appQuestions = apiQuestions.map((apiQ) {
      return QuestionModel(
        id: apiQ.id.toString(),
        categoryId: apiQ.categoryId,
        questionText: apiQ.questionText,
        // Extract option texts
        options: apiQ.options
            .map((opt) => opt.optionText)
            .toList(),
        // For now, assume first option is correct (this should come from API)
        correctIndex: 0,
      );
    }).toList();

    // Cache the results
    _cache[categoryId] = appQuestions;

    return appQuestions;
  }

  /// Get all questions (not recommended for large datasets)
  List<QuestionModel> getAll() => _cache.values.expand((list) => list).toList();

  /// Clear cache when needed
  void clearCache() {
    _cache.clear();
  }

  /// Clear specific category cache
  void clearCategoryCache(int categoryId) {
    _cache.remove(categoryId);
  }

  void addQuestion(dynamic q) {
    // Placeholder for legacy compatibility
  }

  void deleteQuestion(String id) {
    // Placeholder for legacy compatibility
  }
}

