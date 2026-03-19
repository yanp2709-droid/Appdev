import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/quiz/data/models/question.dart';
import '../features/quiz/data/models/quiz_attempt.dart';

class AttemptStartResponse {
  final QuizAttempt attempt;
  final List<QuestionModel> questions;

  AttemptStartResponse({
    required this.attempt,
    required this.questions,
  });
}

/// Service for quiz attempt lifecycle (start, save, submit, status)
class QuizAttemptService {
  final ApiClient apiClient = ApiClient();

  Future<AttemptStartResponse> startAttempt({
    int? quizId,
    int? categoryId,
    int? limit,
    bool random = false,
  }) async {
    try {
      final response = await apiClient.dio.post(
        '/quiz/attempt',
        data: {
          if (quizId != null) 'quiz_id': quizId,
          if (categoryId != null) 'category_id': categoryId,
          if (limit != null) 'limit': limit,
          if (random) 'random': true,
        },
      );

      final data = response.data as Map<String, dynamic>;
      final payload = data['data'] as Map<String, dynamic>? ?? {};
      final attemptJson = payload['attempt'] as Map<String, dynamic>? ?? {};
      final questionsJson = payload['questions'] as List<dynamic>? ?? [];

      final attempt = QuizAttempt.fromJson(attemptJson);
      final questions = questionsJson
          .map((q) => QuestionModel.fromJson(q as Map<String, dynamic>))
          .toList();

      return AttemptStartResponse(
        attempt: attempt,
        questions: questions,
      );
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      if (status == 409) {
        final errorData = e.response?.data as Map<String, dynamic>?;
        if (errorData?['error']?['code'] == 'active_attempt_exists') {
          final details = errorData?['error']?['details'] as Map<String, dynamic>? ?? {};
          final msg = errorData?['error']?['message'] as String? ?? 'Active attempt already exists';
          throw ApiException(
            message: msg,
            statusCode: 409,
            type: 'active_attempt_exists',
          );
        }
      }
      throw apiClient.handleException(e);
    }
  }

  Future<void> saveAnswer({
    required int attemptId,
    required int questionId,
    int? optionId,
    String? textAnswer,
  }) async {
    try {
      await apiClient.dio.post(
        '/quiz/attempts/$attemptId/answer',
        data: {
          'question_id': questionId,
          if (optionId != null) 'option_id': optionId,
          if (textAnswer != null && textAnswer.trim().isNotEmpty)
            'text_answer': textAnswer.trim(),
        },
      );
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  Future<Map<String, dynamic>> submitAttempt({required int attemptId}) async {
    try {
      final response = await apiClient.dio.post(
        '/quiz/attempts/$attemptId/submit',
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  Future<Map<String, dynamic>> getAttemptStatus({required int attemptId}) async {
    try {
      final response = await apiClient.dio.get(
        '/quiz/attempts/$attemptId',
      );
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
