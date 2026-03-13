import 'package:dio/dio.dart';
import '../core/network/api_client.dart';

/// Service for testing API connectivity
class PingService {
  final ApiClient apiClient = ApiClient();

  /// Test the API connection with /test endpoint
  /// 
  /// Returns: {message: 'API working'}
  /// Throws: ApiException on failure
  Future<Map<String, dynamic>> testConnection() async {
    try {
      final response = await apiClient.dio.get('/test');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Ping endpoint (alternative test)
  Future<Map<String, dynamic>> ping() async {
    try {
      final response = await apiClient.dio.get('/ping');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
