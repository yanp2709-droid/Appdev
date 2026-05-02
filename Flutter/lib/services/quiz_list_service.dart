import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/quiz/data/models/quiz.dart';

/// Service for fetching quiz lists from Laravel API
class QuizListService {
  final ApiClient apiClient = ApiClient();

  /// Fetch all quizzes for a specific subject
  ///
  /// Parameters:
  ///   - subjectId: The ID of the subject/category
  ///
  /// Returns: List of QuizModel for the subject (only active quizzes by default)
  /// Throws: ApiException on failure
  Future<List<QuizModel>> getQuizzesBySubject(int subjectId, {bool activeOnly = true}) async {
    try {
      Response<dynamic> response;
      try {
        response = await apiClient.dio.get(
          '/subjects/$subjectId/quizzes',
          queryParameters: activeOnly ? {'is_active': true} : null,
        );
      } on DioException catch (e) {
        if (e.response?.statusCode != 404) {
          rethrow;
        }
        // Backward compatibility for backends that still expose category-based routes.
        response = await apiClient.dio.get(
          '/categories/$subjectId/quizzes',
          queryParameters: activeOnly ? {'is_active': true} : null,
        );
      }

      // Handle both array response and object with data field
      List<dynamic> quizzesData;

      if (response.data is List) {
        quizzesData = response.data as List<dynamic>;
      } else if (response.data is Map<String, dynamic>) {
        final data = response.data as Map<String, dynamic>;
        quizzesData = data['data'] as List<dynamic>? ?? [];
      } else {
        throw ApiException(
          message: 'Unexpected response format',
          type: 'parse_error',
        );
      }

      return quizzesData
          .map((json) => QuizModel.fromJson(json as Map<String, dynamic>))
          .toList();
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Fetch a single quiz with details (including questions count)
  ///
  /// Parameters:
  ///   - quizId: The ID of the quiz
  ///
  /// Returns: QuizModel with full details
  /// Throws: ApiException on failure
  Future<QuizModel> getQuizDetails(int quizId) async {
    try {
      final response = await apiClient.dio.get('/quizzes/$quizId');

      Map<String, dynamic> quizData;

      if (response.data is Map<String, dynamic>) {
        final data = response.data as Map<String, dynamic>;
        quizData = data['data'] as Map<String, dynamic>? ?? data;
      } else {
        throw ApiException(
          message: 'Unexpected response format',
          type: 'parse_error',
        );
      }

      return QuizModel.fromJson(quizData);
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Check if a quiz is available/active
  ///
  /// Parameters:
  ///   - quizId: The ID of the quiz
  ///
  /// Returns: true if quiz is active and available
  /// Throws: ApiException on failure
  Future<bool> isQuizActive(int quizId) async {
    try {
      final response = await apiClient.dio.get('/quizzes/$quizId/availability');

      if (response.data is Map<String, dynamic>) {
        final data = response.data as Map<String, dynamic>;
        return data['is_active'] as bool? ?? data['available'] as bool? ?? true;
      }
      return true;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
