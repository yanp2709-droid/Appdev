/// Custom exception for API errors
class ApiException implements Exception {
  final String message;
  final int? statusCode;
  final String type;

  ApiException({
    required this.message,
    this.statusCode,
    this.type = 'unknown',
  });

  @override
  String toString() => 'ApiException: [$type] $message (Status: $statusCode)';
}
