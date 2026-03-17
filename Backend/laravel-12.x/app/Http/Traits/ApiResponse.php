<?php

namespace App\Http\Traits;

use Illuminate\Validation\ValidationException;

/**
 * Standardized API response formatting
 *
 * All endpoints should use these methods to ensure consistent error/success formats
 */
trait ApiResponse
{
    /**
     * Standard error codes and HTTP status mappings
     */
    private const ERROR_CODE_MAP = [
        'validation_error' => 422,
        'unauthorized' => 401,
        'forbidden' => 403,
        'not_found' => 404,
        'conflict' => 409,
        'gone' => 410,
        'rate_limited' => 429,
        'server_error' => 500,

        // Domain-specific codes
        'quiz_not_found' => 404,
        'attempt_not_found' => 404,
        'active_attempt_exists' => 409,
        'attempt_submitted' => 409,
        'attempt_expired' => 410,
        'invalid_question' => 422,
        'invalid_option' => 422,
        'answer_required' => 422,
        'scoring_failed' => 500,
        'import_rejected' => 422,
        'file_invalid' => 422,
    ];

    /**
     * Return a standardized success response
     *
     * @param array $data Response data
     * @param string $message Success message
     * @param int $status HTTP status code (default 200)
     */
    protected function success(array $data, string $message = 'Success', int $status = 200)
    {
        $payload = [
            'success' => true,
            'message' => $message,
            'data' => $data,
        ];

        // Use JSON_PRESERVE_ZERO_FRACTION to ensure float values like 70.0 are not converted to 70
        $jsonContent = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);

        return response($jsonContent, $status)
            ->header('Content-Type', 'application/json');
    }

    /**
     * Return a standardized error response
     *
     * @param string $code Error code (used for client-side handling)
     * @param string $message Human-readable error message
     * @param int|null $status HTTP status code (auto-detected from code if null)
     * @param array|null $details Additional error details (validation errors, etc)
     */
    protected function error(string $code, string $message, int $status = null, $details = null)
    {
        // Auto-detect status from code
        if ($status === null) {
            $status = self::ERROR_CODE_MAP[$code] ?? 400;
        }

        $payload = [
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
            ],
        ];

        if ($details !== null) {
            $payload['error']['details'] = $details;
        }

        return response()->json($payload, $status);
    }

    /**
     * Return validation error with structured details
     */
    protected function validationError(ValidationException $exception, string $message = 'Validation failed')
    {
        return $this->error(
            'validation_error',
            $message,
            422,
            $exception->errors()
        );
    }

    /**
     * Return a standardized conflict error (409)
     */
    protected function conflict(string $code, string $message, $details = null)
    {
        return $this->error($code, $message, 409, $details);
    }

    /**
     * Return a standardized not found error (404)
     */
    protected function notFound(string $code = 'not_found', string $message = 'Resource not found')
    {
        return $this->error($code, $message, 404);
    }

    /**
     * Return a standardized unauthorized error (401)
     */
    protected function unauthorized(string $message = 'Unauthorized')
    {
        return $this->error('unauthorized', $message, 401);
    }

    /**
     * Return a standardized forbidden error (403)
     */
    protected function forbidden(string $message = 'Forbidden')
    {
        return $this->error('forbidden', $message, 403);
    }

    /**
     * Return a standardized gone error (410)
     */
    protected function gone(string $code, string $message)
    {
        return $this->error($code, $message, 410);
    }

    /**
     * Return a standardized server error (500)
     */
    protected function serverError(string $code = 'server_error', string $message = 'Internal server error')
    {
        return $this->error($code, $message, 500);
    }

    /**
     * Get HTTP status for an error code
     */
    protected function getStatusForCode(string $code): int
    {
        return self::ERROR_CODE_MAP[$code] ?? 400;
    }
}
