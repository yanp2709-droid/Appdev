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
        // Handle 401 (token expired)
        if (error.response?.statusCode == 401) {
          await TokenStorage.deleteToken();
          // TODO: redirect user to login
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
      return ApiException(
        message: 'Unauthorized - token expired or invalid',
        statusCode: 401,
        type: 'unauthorized',
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
