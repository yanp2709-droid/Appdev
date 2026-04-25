import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../features/quiz/data/models/attempt_history.dart';
import '../features/quiz/data/models/attempt_detail.dart';

class AttemptHistoryService {
  final ApiClient apiClient = ApiClient();

  /// Get all completed attempts for the student (with pagination)
  Future<List<AttemptHistoryModel>> getHistory(
      {int page = 1, int perPage = 15}) async {
    try {
      final response = await apiClient.dio.get(
        '/quiz/attempts',
        queryParameters: {
          'page': page,
          'per_page': perPage,
        },
      );
      final data = response.data as Map<String, dynamic>;
      final attempts = (data['data']['attempts'] as List<dynamic>? ?? [])
          .map((a) => AttemptHistoryModel.fromJson(a as Map<String, dynamic>))
          .toList();
      return attempts;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Get detailed review of a specific attempt with per-question breakdown
  Future<AttemptDetailModel> getAttemptDetail({required int attemptId}) async {
    try {
      final response =
          await apiClient.dio.get('/quiz/attempts/$attemptId/detail');
      final data = response.data as Map<String, dynamic>;
      final detail =
          AttemptDetailModel.fromJson(data['data'] as Map<String, dynamic>);
      return detail;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
