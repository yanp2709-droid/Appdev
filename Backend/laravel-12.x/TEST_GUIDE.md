# Quick Start: Running Backend Tests

## Prerequisites

Ensure your Laravel environment is set up:

```bash
cd Backend/laravel-12.x
composer install
cp .env.example .env
php artisan key:generate
```

## Configure Test Database

Update `.env.testing` to use a test SQLite database:

```env
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
```

Or use a test database file:

```env
DB_CONNECTION=sqlite
DB_DATABASE=database/test.sqlite
```

## Run All Tests

```bash
# Run all feature tests
php artisan test

# Run all tests with coverage
php artisan test --coverage

# Run with verbose output
php artisan test --verbose

# Run specific test class
php artisan test tests/Feature/QuizAttemptTest.php

# Run specific test method
php artisan test tests/Feature/QuizAttemptTest.php::test_student_can_start_quiz_attempt
```

## Running Tests by Category

```bash
# Quiz attempt lifecycle tests
php artisan test tests/Feature/QuizAttemptTest.php

# Scoring and results tests
php artisan test tests/Feature/QuizScoringTest.php

# Import/export tests
php artisan test tests/Feature/QuestionImportExportTest.php

# All API tests
php artisan test tests/Feature/

# All auth tests
php artisan test tests/Feature/AuthTest.php
```

## Understanding Test Output

Successful test output:

```
   PASS  tests/Feature/QuizAttemptTest.php
  ✓ student can start quiz attempt
  ✓ student cannot have multiple active attempts
  ✓ unauthorized user cannot start attempt
  ... 11 more

   PASS  tests/Feature/QuizScoringTest.php
  ✓ student can submit attempt
  ✓ scoring correctly counts answers
  ... 6 more

Tests:  36 passed (456 assertions)
```

Failed test output (example):

```
FAIL  tests/Feature/QuizAttemptTest.php
  ✗ student can start quiz attempt
    Expected status 200, got 422
    Expected JSON structure not found

Tests:  36 passed, 1 failed
```

## Key Test Scenarios

### 1. Quiz Attempt Lifecycle

```bash
# Start attempt
POST /api/quiz/attempt
{
  "quiz_id": 1
}

# Save answer
POST /api/quiz/attempts/{attemptId}/answer
{
  "question_id": 1,
  "option_id": 2
}

# Submit attempt
POST /api/quiz/attempts/{attemptId}/submit

# Check status
GET /api/quiz/attempts/{attemptId}
```

### 2. Error Scenarios

All endpoints now return consistent error format:

```json
{
  "success": false,
  "error": {
    "code": "validation_error",
    "message": "Invalid request",
    "details": {
      "quiz_id": ["The quiz_id field is required."]
    }
  }
}
```

**Common Error Codes:**
- `validation_error` (422) - Input validation failed
- `unauthorized` (401) - Authentication required
- `forbidden` (403) - User lacks permission
- `not_found` (404) - Resource doesn't exist
- `conflict` (409) - Resource already exists
- `attempt_expired` (410) - Attempt time exceeded
- `server_error` (500) - Unexpected error

### 3. Import/Export Tests

```bash
# Export JSON
GET /api/admin/questions/export/json

# Export CSV
GET /api/admin/questions/export/csv

# Import JSON
POST /api/admin/questions/import/json
{
  "questions": [...]
}

# Import CSV
POST /api/admin/questions/import/csv
(multipart file upload)
```

## Debugging Failed Tests

### View Database State

Add to test after failure:

```php
// Check if record exists
$this->assertDatabaseHas('quiz_attempts', [
    'student_id' => $this->student->id,
]);

// Check record count
$this->assertDatabaseCount('quiz_attempts', 1);
```

### Print Debug Information

```php
// Dump response
dump($response->json());

// Print SQL queries
DB::listen(function ($query) {
    echo $query->sql . "\n";
});

// Check model state
echo $attempt->toJson();
```

### Run Single Test with Output

```bash
php artisan test tests/Feature/QuizAttemptTest.php::test_student_can_start_quiz_attempt --verbose
```

## Test Database Cleanup

Tests automatically clean up between runs using `RefreshDatabase` trait.

To manually reset test database (if using file-based SQLite):

```bash
rm database/test.sqlite
php artisan migrate --env=testing
```

## CI/CD Integration

The tests are designed to work in CI pipelines:

```yaml
# GitHub Actions example
- name: Run Tests
  run: |
    php artisan test --min-coverage 80
```

### GitLab CI Example

```yaml
test:
  script:
    - php artisan test
  coverage: '/TOTAL.*?(\d+(?:\.\d+)?%?)/'
```

## Performance Notes

- Total test suite: ~30 seconds
- Individual test: ~0.5-2 seconds
- Uses in-memory SQLite for fast execution
- Parallelization available with phpunit:

```bash
php artisan test --parallel
```

## Common Issues

### Tests Timeout

Increase timeout in `phpunit.xml`:

```xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<ini name="default_socket_timeout" value="30"/>
```

### Role Authorization Fails

Ensure users have role assigned:

```php
$user = User::factory()->create(['role' => 'student']);
```

### Migration Issues

Rebuild test database:

```bash
php artisan migrate:refresh --env=testing
php artisan test
```

## Next Steps

1. ✅ Run all tests: `php artisan test`
2. ✅ Check coverage: `php artisan test --coverage`
3. ✅ Review failed tests in output
4. ✅ Deploy confident that quality gates are met
5. ✅ Set up CI/CD to run tests on every push

## Resources

- Laravel Testing: https://laravel.com/docs/testing
- PHPUnit Docs: https://phpunit.de/getting-started.html
- Test Factories: https://laravel.com/docs/eloquent-factories
