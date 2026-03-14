import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/network/token_storage.dart';

/// Service for authentication operations
class AuthService {
  final ApiClient apiClient = ApiClient();

  /// Login with email and password
  /// 
  /// Returns: {token: 'authentication_token', user: {...}}
  /// Throws: ApiException on failure
  Future<Map<String, dynamic>> login({
    required String email,
    required String password,
  }) async {
    try {
      final response = await apiClient.dio.post(
        '/auth/login',
        data: {
          'email': email,
          'password': password,
        },
      );

      final data = response.data as Map<String, dynamic>;

      // Store token if provided
      if (data.containsKey('token')) {
        await TokenStorage.saveToken(data['token'] as String);
      }

      return data;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Get current user profile
  /// 
  /// Requires authentication token
  /// Returns: User object
  /// Throws: ApiException on failure
  Future<Map<String, dynamic>> getMe() async {
    try {
      final response = await apiClient.dio.get('/auth/me');
      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  /// Logout and delete token
  /// 
  /// Requires authentication token
  /// Throws: ApiException on failure
  Future<void> logout() async {
    try {
      await apiClient.dio.post('/auth/logout');
      await TokenStorage.deleteToken();
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }
}
