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

/// Validation error with field-level messages
class ApiValidationException extends ApiException {
  final Map<String, List<String>> fieldErrors;

  ApiValidationException({
    required super.message,
    super.statusCode,
    super.type = 'validation',
    Map<String, List<String>>? fieldErrors,
  }) : fieldErrors = fieldErrors ?? {};
}
