import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/exceptions/api_exception.dart';
import '../features/quiz/data/models/question.dart';
import '../features/quiz/data/models/quiz_attempt.dart';
import '../features/quiz/data/models/attempt_resume.dart';
import './questions_service.dart';
import './quiz_service.dart';

class AttemptStartResponse {
  final QuizAttempt attempt;
  final List<QuestionModel> questions;
  final List<AttemptSavedAnswer> savedAnswers;
  final AttemptProgress? progress;
  final bool resumed;

  AttemptStartResponse({
    required this.attempt,
    required this.questions,
    required this.savedAnswers,
    required this.progress,
    required this.resumed,
  });
}

/// Service for quiz attempt lifecycle (start, save, submit, status)
class QuizAttemptService {
  final ApiClient apiClient = ApiClient();

  ApiException? _mapActiveAttempt(DioException e) {
    final status = e.response?.statusCode;
    if (status != 409) return null;
    final errorData = e.response?.data as Map<String, dynamic>?;
    if (errorData?['error']?['code'] == 'active_attempt_exists') {
      final msg = errorData?['error']?['message'] as String? ?? 'Active attempt already exists';
      return ApiException(
        message: msg,
        statusCode: 409,
        type: 'active_attempt_exists',
      );
    }
    return null;
  }

  Future<Map<String, dynamic>> _postAttempt({
    required String endpoint,
    int? quizId,
    int? categoryId,
    int? limit,
    bool random = false,
  }) async {
    final response = await apiClient.dio.post(
      endpoint,
      data: {
        if (quizId != null) 'quiz_id': quizId,
        if (categoryId != null) 'category_id': categoryId,
        if (limit != null) 'limit': limit,
        if (random) 'random': true,
      },
    );
    return response.data as Map<String, dynamic>;
  }

  Future<int?> _resolveQuizId({int? quizId, int? categoryId}) async {
    if (quizId != null) return quizId;
    if (categoryId == null) return null;
    try {
      final quizzes = await QuizService().getQuizzesByCategory(categoryId);
      if (quizzes.isEmpty) return null;
      final first = quizzes.first;
      if (first is Map<String, dynamic>) {
        return (first['id'] as num?)?.toInt();
      }
    } catch (_) {
      // Best-effort lookup only; fall back to category-only start.
    }
    return null;
  }

  List<dynamic> _extractQuestionsList(dynamic value) {
    if (value is List) return value;
    if (value is Map<String, dynamic>) {
      final direct = value['questions'];
      if (direct is List) return direct;
      final data = value['data'];
      if (data is List) return data;
      if (data is Map<String, dynamic>) {
        final nested = data['questions'];
        if (nested is List) return nested;
      }
    }
    return const <dynamic>[];
  }

  Future<AttemptStartResponse> startAttempt({
    int? quizId,
    int? categoryId,
    int? limit,
    bool random = false,
  }) async {
    try {
      final resolvedQuizId = await _resolveQuizId(quizId: quizId, categoryId: categoryId);
      Map<String, dynamic> data;
      try {
        data = await _postAttempt(
          endpoint: '/quiz/attempt',
          quizId: resolvedQuizId ?? quizId,
          categoryId: categoryId,
          limit: limit,
          random: random,
        );
      } on DioException catch (e) {
        final mapped = _mapActiveAttempt(e);
        if (mapped != null) throw mapped;
        final status = e.response?.statusCode;
        final canRetry = resolvedQuizId != null && (status == 404 || status == 500);
        if (canRetry) {
          try {
            data = await _postAttempt(
              endpoint: '/quiz/$resolvedQuizId/attempt',
              quizId: resolvedQuizId,
              categoryId: categoryId,
              limit: limit,
              random: random,
            );
          } on DioException catch (retryError) {
            final retryMapped = _mapActiveAttempt(retryError);
            if (retryMapped != null) throw retryMapped;
            throw apiClient.handleException(retryError);
          }
        } else {
          throw apiClient.handleException(e);
        }
      }

      final payload = data['data'] as Map<String, dynamic>? ?? {};
      final attemptJson = payload['attempt'] as Map<String, dynamic>? ?? {};
      final progressJson = payload['progress'] as Map<String, dynamic>? ??
          (data['progress'] as Map<String, dynamic>?);
      final savedAnswersJson = payload['saved_answers'] as List<dynamic>? ??
          (data['saved_answers'] as List<dynamic>?) ??
          const <dynamic>[];
      final resumed = payload['resumed'] as bool? ??
          (data['resumed'] as bool?) ??
          false;
      var questionsJson = _extractQuestionsList(payload);
      if (questionsJson.isEmpty) {
        questionsJson = _extractQuestionsList(data);
      }

      final attempt = QuizAttempt.fromJson(attemptJson);
      var questions = questionsJson
          .map((q) => QuestionModel.fromJson(q as Map<String, dynamic>))
          .toList();
      final savedAnswers = savedAnswersJson
          .whereType<Map<String, dynamic>>()
          .map(AttemptSavedAnswer.fromJson)
          .toList();
      final progress = progressJson == null ? null : AttemptProgress.fromJson(progressJson);

      // Fallback to direct category questions if the attempt payload is empty.
      if (questions.isEmpty && categoryId != null) {
        final fallbackQuestions = await QuestionsService().getQuestionsByCategory(
          categoryId: categoryId,
          limit: limit ?? 10,
          random: random,
        );
        if (fallbackQuestions.isNotEmpty) {
          questions = fallbackQuestions;
        }
      }

      return AttemptStartResponse(
        attempt: attempt,
        questions: questions,
        savedAnswers: savedAnswers,
        progress: progress,
        resumed: resumed,
      );
    } on DioException catch (e) {
      final mapped = _mapActiveAttempt(e);
      if (mapped != null) throw mapped;
      throw apiClient.handleException(e);
    }
  }

  Future<void> saveAnswer({
    required int attemptId,
    required int questionId,
    int? optionId,
    List<int>? optionIds,
    String? textAnswer,
    String? answer,
    bool? isBookmarked,
    int? lastViewedQuestionId,
    int? lastViewedQuestionIndex,
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
          if (answer != null && answer.trim().isNotEmpty)
            'answer': answer.trim(),
          if (isBookmarked != null) 'is_bookmarked': isBookmarked,
          if (lastViewedQuestionId != null)
            'last_viewed_question_id': lastViewedQuestionId,
          if (lastViewedQuestionIndex != null)
            'last_viewed_question_index': lastViewedQuestionIndex,
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
