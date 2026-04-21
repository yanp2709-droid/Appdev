# Validation & Error Handling Quick Reference

## ApiResponse Trait Usage

All controllers now inherit `ApiResponse` trait providing standardized methods:

```php
use App\Http\Traits\ApiResponse;

class MyController extends Controller
{
    public function store(Request $request)
    {
        // Success response
        return $this->success([
            'user' => $user,
            'attempt' => $attempt
        ], 'Quiz attempt started successfully', 201);

        // Validation error
        return $this->error('validation_error', 'Missing required fields', 422);

        // Not found
        return $this->notFound('quiz_not_found', 'Quiz not found');
        
        // Conflict (duplicate)
        return $this->conflict('active_attempt_exists', 'Already has active attempt');
        
        // Expired
        return $this->gone('attempt_expired', 'Attempt time exceeded');
        
        // Server error
        return $this->serverError('scoring_failed', 'Could not calculate score');
    }
}
```

## Standard Error Codes Reference

| Code | HTTP Status | Use Case |
|------|-------------|----------|
| validation_error | 422 | Input validation failed |
| unauthorized | 401 | Missing auth token |
| forbidden | 403 | User lacks permission/role |
| not_found | 404 | Resource doesn't exist |
| quiz_not_found | 404 | Specific: quiz not found |
| attempt_not_found | 404 | Specific: attempt not found |
| active_attempt_exists | 409 | Duplicate active attempt |
| attempt_submitted | 409 | Already submitted |
| attempt_expired | 410 | Time exceeded |
| invalid_question | 422 | Question validation |
| invalid_option | 422 | Option validation |
| answer_required | 422 | Answer missing |
| scoring_failed | 500 | Scoring processing error |
| import_rejected | 422 | File import validation |
| file_invalid | 422 | File format error |
| server_error | 500 | Unexpected error |
| invalid_current_password | 422 | Current password verification failed |
| password_change_failed | 500 | Password update error |

## Response Format

### Success Response
```json
{
  "success": true,
  "message": "Operation completed successfully",
  "data": {
    "id": 1,
    "name": "Quiz 1",
    ...
  }
}
```

**HTTP Status**: 200 (or custom status)

### Error Response
```json
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Input validation failed",
    "details": {
      "email": ["The email field is required."],
      "password": ["The password must be at least 8 characters."]
    }
  }
}
```

**HTTP Status**: Matches code (422 for validation_error, 404 for not_found, etc.)

## Form Request Validation

### StartQuizAttemptRequest
```php
$request->validate([
    'quiz_id' => 'nullable|integer|exists:quizzes,id|required_without:category_id',
    'category_id' => 'nullable|integer|exists:categories,id|required_without:quiz_id',
    'limit' => 'nullable|integer|min:1|max:200',
    'random' => 'nullable|boolean',
]);
```

### SaveAnswerRequest
```php
$request->validate([
    'question_id' => 'required|integer|exists:questions,id',
    'option_id' => 'nullable|integer|exists:question_options,id',
    'text_answer' => 'nullable|string|max:5000',
]);
// At least one of option_id or text_answer is required
```

### ImportQuestionsJsonRequest
```php
$request->validate([
    'questions' => 'required|array|min:1',
    'questions.*.question_text' => 'required|string|max:1000',
    'questions.*.category' => 'required|string|max:100',
    'questions.*.question_type' => 'required|in:mcq,tf,ordering,short_answer',
    'questions.*.options' => 'nullable|array',
    'questions.*.options.*' => 'nullable|string|max:500',
    'questions.*.correct_answer' => 'nullable|string|max:500',
    'questions.*.points' => 'nullable|integer|min:1|max:1000',
    'questions.*.answer_key' => 'nullable|string|max:1000',
]);
```

## Business Logic Validation

Use `QuizAttemptValidator` for state and business rules:

```php
// Check if user owns attempt
$validator->validateOwnership($attempt, $userId);

// Check and mark expiration
$isExpired = $validator->validateAndUpdateExpiration($attempt);

// Check if attempt can receive answers
if (!$validator->isAttemptActive($attempt)) {
    return $this->error('attempt_expired', 'Attempt cannot receive answers');
}

// Validate question belongs to quiz
if (!$validator->questionBelongsToQuiz($question, $attempt)) {
    return $this->error('invalid_question', 'Question does not belong to this quiz');
}

// Validate option belongs to question
if (!$validator->optionBelongsToQuestion($optionId, $question)) {
    return $this->error('invalid_option', 'Option does not belong to this question');
}

// Validate answer based on question type
if (!$validator->validateAnswer($questionType, $optionId, $textAnswer)) {
    return $this->error('answer_required', 'Answer is required');
}

// Get remaining seconds
$remaining = $validator->getRemainingSeconds($attempt);

// Check for duplicate active attempts
if ($validator->hasDuplicateActiveAttempt($studentId, $quizId)) {
    return $this->conflict('active_attempt_exists', 'Active attempt exists');
}

// Validate can submit
$errors = $validator->canSubmitAttempt($attempt);
```

## Quiz_attempt Model Helpers

```php
$attempt = Quiz_attempt::find(1);

// Check if expired
$isExpired = $attempt->isExpired(); // bool

// Check if active
$isActive = $attempt->isActive(); // bool (not submitted, not expired)

// Get remaining time
$seconds = $attempt->getRemainingSeconds(); // int

// Get duration
$minutes = $attempt->getDurationMinutes(); // int|null

// Mark as expired
$attempt->markExpired(); // self
```

## Question Import Validation

Use `QuestionImportValidator`:

```php
$validator = new QuestionImportValidator();

// Validate question type
$errors = $validator->validateQuestionType('mcq');

// Validate options for question type
$errors = $validator->validateOptions('mcq', ['A', 'B', 'C']);

// Validate points
$errors = $validator->validatePoints(5);

// Validate answer key
$errors = $validator->validateAnswerKey('short_answer', 'Paris');

// Validate field lengths
$errors = $validator->validateFieldLengths($question);
```

## Authorization in Form Requests

All form requests check role:

```php
public function authorize(): bool
{
    return $this->user()->hasRole('student');
    // or
    return $this->user()->hasAnyRole(['admin', 'teacher']);
}
```

## Error Handling Best Practices

### ✅ DO
```php
// Return early on validation
$validator = Validator::make($request->all(), $rules);
if ($validator->fails()) {
    return $this->error('validation_error', 'Invalid input', 422, $validator->errors());
}

// Check ownership before processing
$attempt = Quiz_attempt::find($id);
if (!$validator->validateOwnership($attempt, auth()->id())) {
    return $this->notFound();
}

// Check state before transitions
if (!$validator->isAttemptActive($attempt)) {
    return $this->error('attempt_expired', 'Cannot answer after expiration');
}

// Use specific error codes for client handling
return $this->conflict('active_attempt_exists', 'User already has active attempt');
```

### ❌ DON'T
```php
// Don't use generic error messages
return response()->json(['error' => 'Something went wrong'], 500);

// Don't mix error code naming conventions
// Use snake_case consistently
return $this->error('userNotFound', ...); // WRONG

// Don't expose internal errors
return $this->serverError('Database connection failed: ' . $e->getMessage());

// Don't return validation errors without structure
return response()->json(['message' => 'email field is required'], 422);
```

## Exception Handling

For unexpected errors, wrap in try-catch:

```php
try {
    $score = $scorer->safeScore($attempt->id);
} catch (\Throwable $e) {
    Log::error('Scoring failed', [
        'attempt_id' => $attempt->id,
        'error' => $e->getMessage(),
    ]);
    return $this->serverError('scoring_failed', 'Could not calculate score');
}
```

## Testing Validation

All validations are tested:

```php
// Test validation failure
$response = $this->postJson('/api/endpoint', [
    'quiz_id' => 999999, // Invalid ID
]);
$response->assertStatus(422)
         ->assertJsonPath('error.code', 'validation_error');

// Test authorization
$response = $this->actingAs($student)->postJson('/api/admin/import', []);
$response->assertStatus(403)
         ->assertJsonPath('error.code', 'forbidden');

// Test state validation
$response = $this->postJson("/api/quiz/attempts/{$expiredAttempt->id}/answer", [...]);
$response->assertStatus(410)
         ->assertJsonPath('error.code', 'attempt_expired');
```

## Checklist for New Endpoints

When adding new endpoints:

- [ ] Use ApiResponse trait for consistent formatting
- [ ] Create form request for input validation
- [ ] Use ValidationService for business rules
- [ ] Return appropriate error code
- [ ] Write tests for success and all error paths
- [ ] Document error responses in swagger/postman
- [ ] Test role-based authorization
- [ ] Verify error messages are user-friendly
- [ ] Log unexpected errors
- [ ] Handle database locks/transactions properly
