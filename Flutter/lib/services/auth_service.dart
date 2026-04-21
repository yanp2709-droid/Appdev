import 'package:dio/dio.dart';
import '../core/network/api_client.dart';
import '../core/network/token_storage.dart';

/// Service for authentication operations
class AuthService {
  final ApiClient apiClient = ApiClient();

  /// Register new student
  ///
  /// Returns: {message: 'Registration successful', user: {...}}
  /// Throws: ApiException or ApiValidationException on failure
  Future<Map<String, dynamic>> registerStudent({
    required String firstName,
    required String lastName,
    required String email,
    required String studentId,
    required String section,
    required String yearLevel,
    required String course,
    required String password,
    required String passwordConfirmation,
    required bool privacyConsent,
  }) async {
    try {
      final response = await apiClient.dio.post(
        '/auth/register',
        data: {
          'first_name': firstName,
          'last_name': lastName,
          'email': email,
          'student_id': studentId,
          'section': section,
          'year_level': yearLevel,
          'course': course,
          'password': password,
          'password_confirmation': passwordConfirmation,
          'privacy_consent': privacyConsent,
        },
      );

      return response.data as Map<String, dynamic>;
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

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

      final raw = response.data as Map<String, dynamic>;

      // Normalize backend response to {token, user}
      String? token;
      if (raw['token'] != null) {
        token = raw['token'] as String?;
      } else if (raw['data'] is Map && (raw['data'] as Map)['token'] != null) {
        token = (raw['data'] as Map)['token'] as String?;
      }

      if (token != null) {
        await TokenStorage.saveToken(token);
      }

      final user = raw['user'];
      return {
        if (token != null) 'token': token,
        if (user != null) 'user': user,
      };
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

  /// Logout - clears local token (server optional)
  ///
  /// Always succeeds locally, ignores server errors
  Future<void> logout() async {
    // Always clear local token first
    await TokenStorage.deleteToken();

    try {
      // Optional: notify server (ignore 401 expired token)
      await apiClient.dio.post('/auth/logout');
    } on DioException catch (e) {
      // Ignore server errors (expected for expired tokens)
      if (e.response?.statusCode != 401) {
        // Re-throw non-401 errors
        throw apiClient.handleException(e);
      }
      // 401 expected/handled, silently ignore
    }
  }

  /// Get all users (admin only)
  /// 
  /// Returns: List of users
  /// Throws: ApiException
  Future<List<Map<String, dynamic>>> getAllUsers() async {
    try {
      final response = await apiClient.dio.get('/admin/users');
      final data = response.data;
      return List<Map<String, dynamic>>.from(data['data'] ?? data);
    } on DioException catch (e) {
      throw apiClient.handleException(e);
    }
  }

  }


