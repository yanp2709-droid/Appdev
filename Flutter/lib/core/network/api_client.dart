import 'package:dio/dio.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import '../config/api_config.dart';
import '../exceptions/api_exception.dart';
import 'token_storage.dart';

class ApiClient {
  late Dio dio;

  ApiClient() {
    dio = Dio(
      BaseOptions(
        baseUrl: ApiConfig.baseUrl,
        connectTimeout: ApiConfig.connectTimeout,
        receiveTimeout: ApiConfig.receiveTimeout,
        headers: ApiConfig.defaultHeaders,
      ),
    );

    // Add authentication interceptor
    dio.interceptors.add(InterceptorsWrapper(
      onRequest: (options, handler) async {
        final token = await TokenStorage.getToken();
        if (token != null) {
          options.headers['Authorization'] = 'Bearer $token';
        }
        return handler.next(options);
      },
      onError: (error, handler) async {
        final response = error.response;
        if (response?.statusCode == 401) {
          final requestPath = response?.requestOptions.path ?? '';
          // Skip auto-logout for auth endpoints (login/register/logout)
          // These handle 401 themselves
          if (requestPath.contains('/auth/login') ||
              requestPath.contains('/auth/register') ||
              requestPath.contains('/auth/logout')) {
            return handler.next(error);
          }
          // For protected endpoints, 401 means token expired
          await TokenStorage.deleteToken();
          throw ApiException(
            message: 'Unauthorized - token expired or invalid. Logging out...',
            statusCode: 401,
            type: 'unauthorized',
          );
        }
        return handler.next(error);
      },
    ));

    // Add pretty logger for debugging
    dio.interceptors.add(
      PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseBody: true,
      ),
    );
  }

  /// Handle DioException and convert to ApiException
  ApiException handleException(DioException e) {
    if (e.type == DioExceptionType.connectionTimeout) {
      return ApiException(
        message: 'Connection timeout - server took too long to respond',
        statusCode: e.response?.statusCode,
        type: 'timeout',
      );
    } else if (e.type == DioExceptionType.receiveTimeout) {
      return ApiException(
        message: 'Receive timeout - waiting for response too long',
        statusCode: e.response?.statusCode,
        type: 'timeout',
      );
    } else if (e.response?.statusCode == 401) {
      // Try to extract error message from response
      final data = e.response?.data;
      String message = 'Unauthorized - invalid credentials';

      if (data is Map) {
        // Try different message locations
        if (data['error'] is Map && data['error']['message'] != null) {
          message = data['error']['message'].toString();
        } else if (data['message'] != null) {
          message = data['message'].toString();
        }
      }

      return ApiException(
        message: message,
        statusCode: 401,
        type: 'unauthorized',
      );
    } else if (e.response?.statusCode == 422) {
      final data = e.response?.data;
      final Map<String, List<String>> errors = {};
      if (data is Map && data['errors'] is Map) {
        (data['errors'] as Map).forEach((key, value) {
          if (value is List) {
            errors[key.toString()] = value.map((v) => v.toString()).toList();
          } else if (value != null) {
            errors[key.toString()] = [value.toString()];
          }
        });
      }
      final message = (data is Map && data['message'] != null)
          ? data['message'].toString()
          : 'Validation failed';
      return ApiValidationException(
        message: message,
        statusCode: 422,
        fieldErrors: errors,
      );
    } else if (e.response?.statusCode == 404) {
      return ApiException(
        message: 'Endpoint not found',
        statusCode: 404,
        type: 'not_found',
      );
    } else if (e.response?.statusCode == 500) {
      return ApiException(
        message: 'Server error - please try again later',
        statusCode: 500,
        type: 'server_error',
      );
    } else if (e.type == DioExceptionType.unknown) {
      return ApiException(
        message: 'Network error - please check your internet connection',
        statusCode: e.response?.statusCode,
        type: 'network_error',
      );
    } else {
      return ApiException(
        message: e.message ?? 'An unknown error occurred',
        statusCode: e.response?.statusCode,
        type: 'unknown',
      );
    }
  }
}
