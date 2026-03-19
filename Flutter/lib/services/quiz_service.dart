import 'package:dio/dio.dart';
import '../core/network/api_client.dart';

/// Service for quiz operations
class QuizService {
  final ApiClient apiClient = ApiClient();

  /// Get all categories
  /// 
  /// Returns: List of category objects
  /// Throws: ApiException on failure
  Future<List<dynamic>> getCategories() async {
    try {
      final response = await apiClient.dio.get('/categories');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Get quizzes by category
  /// 
  /// Parameters:
  ///   - categoryId: The ID of the category
  /// 
  /// Returns: List of quiz objects for the category
  /// Throws: ApiException on failure
  Future<List<dynamic>> getQuizzesByCategory(int categoryId) async {
    try {
      final response = await apiClient.dio.get('/categories/$categoryId/quizzes');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Get quiz details with questions
  /// 
  /// Parameters:
  ///   - quizId: The ID of the quiz
  /// 
  /// Returns: Quiz object with questions
  /// Throws: ApiException on failure
  Future<Map<String, dynamic>> getQuizDetails(int quizId) async {
    try {
      final response = await apiClient.dio.get('/quiz/$quizId');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Submit quiz attempt
  /// 
  /// Parameters:
  ///   - quizId: The ID of the quiz
  ///   - answers: Map of question_id -> selected_option_id
  /// 
  /// Returns: Result object with score and feedback
  /// Throws: ApiException on failure
  Future<Map<String, dynamic>> submitQuizAttempt({
    required int quizId,
    required Map<String, dynamic> answers,
  }) async {
    try {
      final response = await apiClient.dio.post(
        '/quiz/$quizId/attempt',
        data: {'answers': answers},
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Get user's quiz attempt history
  /// 
  /// Requires authentication token
  /// Returns: List of attempt objects
  /// Throws: ApiException on failure
  Future<List<dynamic>> getAttempts() async {
    try {
      final response = await apiClient.dio.get('/attempts');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
