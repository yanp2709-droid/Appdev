class AppException implements Exception {
  final String message;
  const AppException(this.message);
  @override
  String toString() => message;
}

class InvalidCredentialsException extends AppException {
  const InvalidCredentialsException()
      : super('Invalid credentials. Please try again.');
}

class NetworkException extends AppException {
  const NetworkException()
      : super('Network error. Please check your connection.');
}

class SessionExpiredException extends AppException {
  const SessionExpiredException()
      : super('Your session has expired. Please log in again.');
}
