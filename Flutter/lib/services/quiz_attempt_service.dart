import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/quiz/data/models/question.dart';
import '../features/quiz/data/models/quiz_attempt.dart';
import '../features/quiz/data/models/attempt_resume.dart';

class AttemptStartResponse {
  final QuizAttempt attempt;
  final List<QuestionModel> questions;
  final List<AttemptSavedAnswer> savedAnswers;
  final AttemptProgress? progress;
  final AttemptAvailability availability;

  AttemptStartResponse({
    required this.attempt,
    required this.questions,
    required this.savedAnswers,
    required this.progress,
    required this.availability,
  });
}

/// Service for quiz attempt lifecycle (start, save, submit, status)
class QuizAttemptService {
  final ApiClient apiClient = ApiClient();

  Future<AttemptStartResponse> startAttempt({
    int? quizId,
    int? categoryId,
    String attemptType = 'graded',
    int? limit,
    bool random = false,
  }) async {
    try {
      final response = await apiClient.dio.post(
        '/quiz/attempt',
        data: {
          if (quizId != null) 'quiz_id': quizId,
          if (categoryId != null) 'category_id': categoryId,
          'attempt_type': attemptType,
          if (limit != null) 'limit': limit,
          if (random) 'random': true,
        },
      );

      final data = response.data as Map<String, dynamic>;
      final payload = data['data'] as Map<String, dynamic>? ?? {};
      final attemptJson = payload['attempt'] as Map<String, dynamic>? ?? {};
      final questionsJson = payload['questions'] as List<dynamic>? ?? [];
      final savedAnswersRaw = payload['saved_answers'] ?? data['saved_answers'];
      final List<dynamic> savedAnswersJson;
      if (savedAnswersRaw is List<dynamic>) {
        savedAnswersJson = savedAnswersRaw;
      } else if (savedAnswersRaw is Map<String, dynamic>) {
        savedAnswersJson = savedAnswersRaw.values.toList();
      } else {
        savedAnswersJson = const <dynamic>[];
      }
      final progressJson = payload['progress'] as Map<String, dynamic>? ??
          (data['progress'] as Map<String, dynamic>?);
      final availabilityJson = payload['attempt_availability']
              as Map<String, dynamic>? ??
          (data['attempt_availability'] as Map<String, dynamic>? ?? const {});

      final attempt = QuizAttempt.fromJson(attemptJson);
      final questions = questionsJson
          .map((q) => QuestionModel.fromJson(q as Map<String, dynamic>))
          .toList();
      final savedAnswers = savedAnswersJson
          .whereType<Map<String, dynamic>>()
          .map(AttemptSavedAnswer.fromJson)
          .toList();
      final progress =
          progressJson == null ? null : AttemptProgress.fromJson(progressJson);
      final availability = AttemptAvailability.fromJson(availabilityJson);

      return AttemptStartResponse(
        attempt: attempt,
        questions: questions,
        savedAnswers: savedAnswers,
        progress: progress,
        availability: availability,
      );
    } on DioException catch (e) {
      final status = e.response?.statusCode;
      if (status == 409) {
        final errorData = e.response?.data as Map<String, dynamic>?;
        if (errorData?['error']?['code'] == 'active_attempt_exists') {
          final msg = errorData?['error']?['message'] as String? ??
              'Active attempt already exists';
          throw ApiException(
            message: msg,
            statusCode: 409,
            type: 'active_attempt_exists',
          );
        }
      }
      if (status == 403) {
        final errorData = e.response?.data as Map<String, dynamic>?;
        if (errorData?['error']?['code'] == 'graded_attempt_already_used') {
          throw ApiException(
            message: errorData?['error']?['message'] as String? ??
                'You have already used your graded attempt for this quiz. You may still continue in practice mode.',
            statusCode: 403,
            type: 'graded_attempt_already_used',
          );
        }
      }
      throw apiClient.handleException(e);
    }
  }

  Future<AttemptAvailability> getAttemptAvailability({
    int? quizId,
    int? categoryId,
  }) async {
    try {
      final response = await apiClient.dio.get(
        '/quiz/availability',
        queryParameters: {
          if (quizId != null) 'quiz_id': quizId,
          if (categoryId != null) 'category_id': categoryId,
        },
      );

      final data = response.data as Map<String, dynamic>;
      final payload = data['data'] as Map<String, dynamic>? ?? {};
      final availabilityJson =
          payload['attempt_availability'] as Map<String, dynamic>? ?? const {};

      return AttemptAvailability.fromJson(availabilityJson);
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  Future<void> saveAnswer({
    required int attemptId,
    required int questionId,
    int? optionId,
    List<int>? optionIds,
    String? textAnswer,
    bool? isBookmarked,
  }) async {
    try {
      await apiClient.dio.post(
        '/quiz/attempts/$attemptId/answer',
        data: {
          'question_id': questionId,
          if (optionId != null) 'option_id': optionId,
          if (optionIds != null) 'option_ids': optionIds,
          if (textAnswer != null && textAnswer.trim().isNotEmpty)
            'text_answer': textAnswer.trim(),
          if (isBookmarked != null) 'is_bookmarked': isBookmarked,
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

  Future<Map<String, dynamic>> getAttemptStatus(
      {required int attemptId}) async {
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
