import 'package:dio/dio.dart';
import '../core/network/api_client.dart';

class QuizService {
  final ApiClient apiClient = ApiClient();

  Future<List<dynamic>> getCategories() async {
    try {
      final response = await apiClient.dio.get('/categories');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  Future<List<dynamic>> getQuizzesByCategory(int categoryId) async {
    try {
      final response = await apiClient.dio.get('/categories/$categoryId/quizzes');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  Future<Map<String, dynamic>> getQuizDetails(int quizId) async {
    try {
      final response = await apiClient.dio.get('/quiz/$quizId');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

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

  Future<List<dynamic>> getAttempts() async {
    try {
      final response = await apiClient.dio.get('/attempts');
      return response.data as List<dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}