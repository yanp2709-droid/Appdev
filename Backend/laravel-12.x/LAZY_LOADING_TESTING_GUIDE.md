# Lazy Loading Pagination Implementation Guide - Testing

## Implementation Summary

The Laravel backend has been updated to support lazy loading pagination for:
1. **Categories Endpoint** (`GET /api/categories`)
2. **Quizzes by Category/Subject Endpoints** (`GET /api/categories/{id}/quizzes`, `GET /api/subjects/{id}/quizzes`)

## Changes Made

### 1. CategoriesController - Updated `index()` method
- Added validation for `limit` and `page` query parameters
- Supports `academic_year` parameter for filtering
- Uses Laravel's `paginate()` method instead of `get()`
- Returns pagination metadata in response

### 2. QuizzesController - Updated `quizListResponse()` method
- Added validation for `limit`, `page`, `is_active`, and `academic_year` parameters
- Supports pagination with configurable page size
- Returns pagination metadata in response with `has_more` flag

### 3. Database Migration
- Added indexes on `categories` table for faster pagination queries
- Added indexes on `quizzes` table for efficient filtering and sorting
- File: `database/migrations/2026_05_10_000000_add_lazy_loading_pagination_indexes.php`

## API Response Format

### Categories Endpoint Response
```json
{
  "data": [
    {
      "id": 1,
      "name": "Mathematics",
      "description": "Mathematics topics",
      "quiz_count": 5
    },
    {
      "id": 2,
      "name": "Science",
      "description": "Science topics",
      "quiz_count": 3
    }
  ],
  "pagination": {
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5,
    "has_more": true
  },
  "message": "Success"
}
```

### Quizzes Endpoint Response
```json
{
  "success": true,
  "message": "Quizzes retrieved.",
  "category": {
    "id": 1,
    "name": "Mathematics",
    "description": "Mathematics topics"
  },
  "data": [
    {
      "id": 1,
      "subject_id": 1,
      "title": "Quiz 1",
      "description": null,
      "category_id": 1,
      "teacher_id": 1,
      "difficulty": "Easy",
      "question_count": 10,
      "questions_count": 10,
      "time_limit": 30,
      "duration_minutes": 30,
      "is_active": true
    }
  ],
  "pagination": {
    "total": 20,
    "per_page": 10,
    "current_page": 1,
    "last_page": 2,
    "has_more": true
  }
}
```

## Testing Instructions

### Prerequisites
Before testing, ensure:
1. Run the migration to create indexes: `php artisan migrate`
2. Backend server is running
3. Database has sample data with multiple categories and quizzes

### Unit Tests

#### Test 1: Get Categories with Default Pagination
```bash
curl -X GET "http://localhost:8000/api/categories"
```
Expected:
- Status: 200
- Returns first 10 categories
- `pagination.current_page` = 1
- `pagination.per_page` = 10

#### Test 2: Get Categories with Custom Limit
```bash
curl -X GET "http://localhost:8000/api/categories?limit=5&page=1"
```
Expected:
- Returns 5 categories
- `pagination.per_page` = 5

#### Test 3: Get Categories on Second Page
```bash
curl -X GET "http://localhost:8000/api/categories?limit=10&page=2"
```
Expected:
- `pagination.current_page` = 2
- Returns next 10 categories
- `pagination.has_more` = true (if more pages exist)

#### Test 4: Get Categories with Academic Year Filter
```bash
curl -X GET "http://localhost:8000/api/categories?academic_year=2024&limit=10&page=1"
```
Expected:
- Only categories from 2024 academic year
- Pagination works correctly

#### Test 5: Get Categories Beyond Available Pages
```bash
curl -X GET "http://localhost:8000/api/categories?page=999"
```
Expected:
- Status: 200
- `data` = empty array
- `pagination.current_page` = 999
- `pagination.has_more` = false

#### Test 6: Get Quizzes by Category with Pagination
```bash
curl -X GET "http://localhost:8000/api/categories/{categoryId}/quizzes?limit=10&page=1"
```
Expected:
- Status: 200
- Returns quizzes for the category
- Includes pagination metadata
- `pagination.has_more` indicates if more pages exist

#### Test 7: Get Quizzes by Subject with Pagination
```bash
curl -X GET "http://localhost:8000/api/subjects/{subjectId}/quizzes?limit=10&page=1&is_active=true"
```
Expected:
- Status: 200
- Only active quizzes
- Pagination metadata included

#### Test 8: Get Quizzes with Academic Year Filter
```bash
curl -X GET "http://localhost:8000/api/categories/{categoryId}/quizzes?academic_year=2024&limit=10&page=1&is_active=true"
```
Expected:
- Only quizzes from 2024 academic year
- Pagination works with all filters

#### Test 9: Limit Validation
```bash
curl -X GET "http://localhost:8000/api/categories?limit=200&page=1"
```
Expected:
- Status: 422 (Validation Error) OR
- Limit is capped at 100

#### Test 10: Page Number Validation
```bash
curl -X GET "http://localhost:8000/api/categories?limit=10&page=0"
```
Expected:
- Status: 422 (Validation Error) - page must be >= 1

### Integration Tests with Flutter

#### Test 1: Initial Page Load
1. Open Flutter app
2. Navigate to Categories screen
3. Verify first 10 categories load
4. Check that pagination metadata is received

#### Test 2: Infinite Scroll
1. Open Categories screen
2. Scroll to bottom
3. Verify loading indicator appears
4. Verify page 2 is automatically loaded
5. Verify new items are appended to the list

#### Test 3: Multiple Pages
1. Continue scrolling
2. Multiple pages load correctly
3. Loading indicator appears/disappears appropriately
4. Items are not duplicated

#### Test 4: Filtering
1. Open Quizzes in a category
2. Verify only active quizzes show
3. Scroll to load more
4. Verify filter is applied to all pages

#### Test 5: Performance
1. Load categories with limit=50
2. Verify response time is < 200ms
3. Load page 10 with limit=10 (100 items total)
4. Verify response time remains acceptable

### Manual Testing with Postman

1. Import these requests into Postman:

**Categories - Page 1**
```
GET http://localhost:8000/api/categories?limit=10&page=1
```

**Categories - Page 2**
```
GET http://localhost:8000/api/categories?limit=10&page=2
```

**Quizzes - Category 1, Active Only**
```
GET http://localhost:8000/api/categories/1/quizzes?limit=10&page=1&is_active=true
```

**Quizzes - Subject 1, Active with Year Filter**
```
GET http://localhost:8000/api/subjects/1/quizzes?limit=10&page=1&academic_year=2024&is_active=true
```

### Database Verification

After running the migration, verify indexes were created:

**MySQL:**
```sql
SHOW INDEX FROM categories;
SHOW INDEX FROM quizzes;
```

Expected indexes:
- Categories: `idx_categories_published_created`, `idx_categories_published_name`
- Quizzes: `idx_quizzes_category_active_created`, `idx_quizzes_category_created`, `idx_quizzes_is_active_created`

### Performance Testing

Load test with Apache Bench:

```bash
# Test categories endpoint with pagination
ab -n 1000 -c 10 "http://localhost:8000/api/categories?limit=10&page=1"

# Test quizzes endpoint with pagination
ab -n 1000 -c 10 "http://localhost:8000/api/categories/1/quizzes?limit=10&page=1"
```

Expected: Average response time < 100ms

## Troubleshooting

### Issue: Validation errors for limit/page parameters

**Solution:** Ensure the request sends valid parameters:
- `limit`: integer, 1-100
- `page`: integer, >= 1

### Issue: No pagination metadata in response

**Solution:** Check that the controllers have been updated with the latest code from this implementation.

### Issue: Database indexes not being used

**Solution:** 
1. Run migration: `php artisan migrate`
2. Verify indexes exist: `SHOW INDEX FROM categories/quizzes`
3. Check query performance with `EXPLAIN` in MySQL

### Issue: Flutter not showing loading indicator

**Solution:**
1. Verify `has_more` field is in pagination response
2. Check Flutter provider for correct pagination logic
3. Verify scroll threshold is set correctly

## Next Steps

1. **Run Migration**: Execute `php artisan migrate` in Docker or local environment
2. **Test Endpoints**: Use Postman or curl to verify responses
3. **Deploy**: Push changes to production
4. **Monitor**: Track API response times and database query performance

## Backward Compatibility

The new implementation is backward compatible with existing code:
- Old clients that don't send pagination parameters will get first 10 items
- Response still includes `data` field with items array
- Pagination metadata is optional for clients that don't need it

## Performance Considerations

1. **Default Page Size**: 10 items (can be changed via `limit` parameter, max 100)
2. **Sorting**: Categories by name, Quizzes by created_at (descending)
3. **Database Indexes**: Added for faster query execution
4. **Eager Loading**: Used `withCount()` to avoid N+1 queries
5. **Query Timeout**: Set to 60 seconds for safety

## API Documentation

### Categories Endpoint
**URL**: `GET /api/categories`

**Parameters**:
- `limit` (optional): Items per page, 1-100, default: 10
- `page` (optional): Page number, >= 1, default: 1
- `academic_year` (optional): Filter by academic year

**Response**: See format above

**Example**: `GET /api/categories?limit=10&page=1&academic_year=2024`

### Quizzes by Category Endpoint
**URL**: `GET /api/categories/{id}/quizzes`

**Parameters**:
- `limit` (optional): Items per page, 1-100, default: 10
- `page` (optional): Page number, >= 1, default: 1
- `is_active` (optional): Filter by active status, default: true
- `academic_year` (optional): Filter by academic year

**Response**: See format above

**Example**: `GET /api/categories/1/quizzes?limit=10&page=1&is_active=true&academic_year=2024`

### Quizzes by Subject Endpoint (Backward Compatibility)
**URL**: `GET /api/subjects/{id}/quizzes`

**Parameters**: Same as Quizzes by Category

**Response**: Same as Quizzes by Category

---

**Implementation Date**: May 10, 2026
**Version**: 1.0.0 - Initial Lazy Loading Implementation
