import 'api_error.dart';

class Result<T> {
  final T? data;
  final ApiError? error;

  Result.success(this.data) : error = null;
  Result.failure(this.error) : data = null;

  bool get isSuccess => data != null;
}
