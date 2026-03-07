import 'package:dio/dio.dart';
import 'package:pretty_dio_logger/pretty_dio_logger.dart';
import 'token_storage.dart';
import 'api_error.dart';

class ApiClient {
  late Dio dio;

  ApiClient() {
    dio = Dio(
      BaseOptions(
        baseUrl: "http://10.0.2.2:8000/api",
        connectTimeout: const Duration(seconds: 10),
        receiveTimeout: const Duration(seconds: 10),
      ),
    );

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

    dio.interceptors.add(
      PrettyDioLogger(
        requestHeader: true,
        requestBody: true,
        responseBody: true,
      ),
    );
  }
}
