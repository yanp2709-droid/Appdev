class ApiError {
  final int? code;
  final String message;
  final Map<String, dynamic>? details;

  ApiError({
    this.code,
    required this.message,
    this.details,
  });
}
