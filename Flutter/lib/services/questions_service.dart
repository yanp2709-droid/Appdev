import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/quiz/data/models/question.dart';

/// Service for fetching questions from Laravel API
class QuestionsService {
  final ApiClient apiClient = ApiClient();

  /// Fetch questions for a specific category
  /// 
  /// Parameters:
  ///   - categoryId: The ID of the category
  ///   - limit: Maximum number of questions (default: 10)
  ///   - random: Whether to randomize the order (default: false)
  /// 
  /// Returns: List<QuestionModel> for the category
  /// Throws: ApiException on failure
  Future<List<QuestionModel>> getQuestionsByCategory({
    required int categoryId,
    int limit = 10,
    bool random = false,
  }) async {
    try {
      final response = await apiClient.dio.get(
        '/questions',
        queryParameters: {
          'category_id': categoryId,
          'limit': limit,
          if (random) 'random': true,
        },
      );

      // Handle both array response and object with data field
      List<dynamic> questionsData;

      if (response.data is List) {
        questionsData = response.data as List<dynamic>;
      } else if (response.data is Map<String, dynamic>) {
        final data = response.data as Map<String, dynamic>;
        questionsData = data['data'] as List<dynamic>? ?? [];
      } else {
        throw ApiException(
          message: 'Unexpected response format',
          type: 'parse_error',
        );
      }

      return questionsData
          .map((json) => QuestionModel.fromJson(json as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
