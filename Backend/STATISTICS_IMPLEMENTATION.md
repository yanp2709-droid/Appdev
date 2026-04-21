# Student Score Statistics and Average Analytics Implementation

## Overview

This implementation provides comprehensive student quiz performance statistics and analytics for administrators. It allows admins to quickly understand student performance through various statistics and visualizations.

## Features Implemented

### 1. Backend Statistics Service (`QuizStatisticsService`)
Location: `app/Services/QuizStatisticsService.php`

The service provides the following statistical methods:

#### Overall Statistics
```php
$service->getOverallStatistics()
```
Returns:
- `total_students`: Count of all student accounts
- `total_attempts`: Total quiz attempts (all statuses)
- `submitted_attempts`: Completed and graded attempts
- `in_progress_attempts`: Currently active attempts
- `expired_attempts`: Attempts that expired without submission
- `average_score`: Overall average score percentage
- `highest_score`: Highest score across all attempts
- `lowest_score`: Lowest score across all attempts
- `completion_rate`: Percentage of attempts completed

#### Student Statistics
```php
$service->getStudentStatistics($studentId)
```
Returns per-student metrics:
- Total attempts and completion breakdown
- Average, highest, lowest scores
- Performance completion rate

#### Category Statistics
```php
$service->getCategoryStatistics()
```
Returns statistics grouped by quiz category:
- Total attempts per category
- Average scores by category
- Highest/lowest scores by category

#### Quiz Statistics
```php
$service->getQuizStatistics()
```
Returns statistics grouped by individual quiz

#### Date Range Statistics
```php
$service->getStatisticsByDateRange($startDate, $endDate)
```
Returns statistics for a specific time period

#### Performance Distribution
```php
$service->getPerformanceDistribution()
```
Returns grade distribution (A, B, C, D, F) based on score ranges:
- A: 90-100%
- B: 80-89%
- C: 70-79%
- D: 60-69%
- F: 0-59%

#### Difficulty Analysis
```php
$service->getDifficultyAnalysis()
```
Returns statistics grouped by quiz difficulty (easy, medium, hard)

#### Top Students
```php
$service->getTopStudents($limit = 10)
```
Returns top performing students with their average scores

#### Category Card Statistics
```php
$service->getCategoryCardStatistics($dateFrom = null, $dateTo = null)
```
Returns summary cards for each category with attempt counts and scores
- **Parameters**: 
  - `$dateFrom`: Start date (Y-m-d format) - optional
  - `$dateTo`: End date (Y-m-d format) - optional
- **Filters**: When dates provided, only includes attempts created between those dates

#### Category Detail Statistics
```php
$service->getCategoryDetailStatistics($categoryId, $dateFrom = null, $dateTo = null)
```
Returns detailed student performance data for a specific category
- **Parameters**: 
  - `$categoryId`: Category ID (required)
  - `$dateFrom`: Start date (Y-m-d format) - optional
  - `$dateTo`: End date (Y-m-d format) - optional
- **Filters**: When dates provided, only includes attempts created between those dates

### 2. API Endpoints
All endpoints require admin authentication (`role:admin`)

**Base Path**: `/api/admin/statistics/`

#### Dashboard
```
GET /api/admin/statistics/dashboard
```
Returns overall statistics overview

#### All Students
```
GET /api/admin/statistics/students?per_page=20&search=name
```
Returns paginated list of students with their statistics

#### Single Student
```
GET /api/admin/statistics/student/{studentId}
```
Returns detailed statistics for a single student

#### Quiz Attempts
```
GET /api/admin/statistics/attempts?status=submitted&category_id=1&student_id=1&per_page=20
```
Returns filtered quiz attempts with options to filter by:
- `status`: submitted, in_progress, or expired
- `category_id`: Filter by quiz category
- `student_id`: Filter by student
- `per_page`: Pagination size

#### Attempt History
```
GET /api/admin/statistics/attempt-history?per_page=20
```
Returns recent attempts per student

#### Category Statistics
```
GET /api/admin/statistics/categories
```
Returns statistics grouped by category

### 3. Filament Admin Interface

#### Statistics Dashboard Page
**Location**: `app/Filament/Pages/Statistics.php`
**Navigation**: Admin panel → Overall Analytics (sidebar)

**Features**:
- **Date Range Filtering**: Filter all statistics by custom date range
  - From Date / To Date inputs at the top of the page
  - Defaults to last 30 days
  - Real-time filtering with Livewire
  - Reset button to clear filters

Displays:
1. **Category Cards**: Clickable cards showing category performance metrics
   - Total attempts, completion rate, highest/lowest scores
   - Filtered by selected date range

2. **Category Detail View**: When a category card is selected
   - Student performance table for that category
   - Best/worst performers
   - All metrics filtered by date range

3. **Dashboard Stats Overview Widget**: High-level metrics
   - Total Students
   - Total Attempts
   - Submitted Attempts
   - Average Score

4. **Category Performance Widget**: Bar chart showing average scores by category

3. **Student Performance Analytics Widget**: Detailed table of all students with:
   - Name and Email
   - Student ID
   - Total Attempts
   - Average Score (with color coding)
   - Highest Score
   - Last Attempt Date

#### Student Resource Enhancement
**Location**: `app/Filament/Resources/Students/StudentResource.php`

Enhanced student list table shows:
- Name and Email
- Joined Date
- Total Attempts
- **Average Score** (new) - with color coding
  - Green (≥80%)
  - Yellow (60-79%)
  - Red (<60%)
  - Gray (No data)
- **Highest Score** (new) - in green

#### Student Detail View
**Location**: `app/Filament/Resources/Students/Pages/ViewStudent.php`

When viewing a single student, displays:
1. **Student Score Statistics Widget** (new)
   - Total Submitted Attempts
   - Average Score with performance color
   - Highest Score
   - Lowest Score

2. **Quiz Attempts Table Widget**
   - Details of all quiz attempts
   - Answers provided
   - Scores and percentages

### 4. Model Relationships

#### User Model
Added relationship:
```php
public function quizAttempts()
{
    return $this->hasMany(Quiz_attempt::class, 'student_id');
}
```

### 5. Date Range Filtering Implementation

#### Overview
The date range filtering feature allows administrators to view statistics for specific time periods, enabling better analysis of quiz performance trends over time.

#### Implementation Details

**Livewire Properties** (in `Statistics.php`):
```php
public ?string $dateFrom = null;
public ?string $dateTo = null;
```

**Default Date Range**:
- Automatically sets to last 30 days on page load
- Uses Carbon for date calculations

**Reactive Filtering**:
- `updatedDateFrom()` and `updatedDateTo()` methods trigger re-rendering
- Uses `wire:model.live` for real-time updates
- `resetFilters()` method clears all filters

**Service Layer Updates**:
- `getCategoryCardStatistics($dateFrom, $dateTo)` - accepts optional date parameters
- `getCategoryDetailStatistics($categoryId, $dateFrom, $dateTo)` - accepts optional date parameters
- Date filtering uses `whereBetween('created_at', [$dateFrom, $dateTo])` on quiz_attempts table

**UI Components**:
- Date input form at top of statistics page
- Reset button to clear filters
- Form validation ensures valid date ranges

#### Database Queries
Date filtering is applied at the query level:
```sql
WHERE quiz_attempts.created_at BETWEEN ? AND ?
```
This ensures efficient filtering and maintains performance with large datasets.

### 6. Value Formatting

All score values are:
- **Type**: Numeric (float)
- **Format**: Percentages with 2 decimal places (e.g., 87.50%)
- **Range**: 0 to 100
- **Null Handling**: Defaults to 0 if no attempts

## Empty State Handling

The implementation gracefully handles cases where there are no quiz attempts:

1. **No students**: Returns 0 for all counts
2. **Student with no attempts**: Shows "N/A" or 0 in appropriate fields
3. **Empty categories**: Returns empty array without errors
4. **Empty date ranges**: Returns 0 for all metrics

Color coding also handles empty states:
- Gray color for fields with no data
- Appropriate fallback values

## Testing

Two comprehensive test suites have been created:

### Unit/Feature Tests: `QuizStatisticsServiceTest`
Tests for the service layer:
- Overall statistics calculations
- Student individual statistics
- Category grouping
- Performance distribution
- Difficulty analysis
- **Date range filtering** (new)
  - `it_filters_category_card_statistics_by_date_range`
  - `it_filters_category_detail_statistics_by_date_range`
- Empty state handling

### API Tests: `AdminStatisticsApiTest`
Tests for API endpoints:
- Authentication and authorization
- Dashboard endpoint
- Student endpoints with pagination
- Filtering and sorting
- Empty state responses
- Numeric value validation

**Run tests:**
```bash
php artisan test tests/Feature/QuizStatisticsServiceTest
php artisan test tests/Feature/AdminStatisticsApiTest
```

## Usage Examples

### Admin API Usage

#### Get Overall Statistics
```bash
curl -X GET http://localhost:8000/api/admin/statistics/dashboard \
  -H "Authorization: Bearer {admin_token}"
```

Response:
```json
{
  "success": true,
  "message": "Dashboard statistics retrieved.",
  "data": {
    "statistics": {
      "total_students": 25,
      "total_attempts": 150,
      "submitted_attempts": 140,
      "in_progress_attempts": 10,
      "expired_attempts": 0,
      "average_score": 78.50,
      "highest_score": 100.00,
      "lowest_score": 35.00,
      "completion_rate": 93.33
    }
  }
}
```

#### Get Student Statistics
```bash
curl -X GET http://localhost:8000/api/admin/statistics/students?per_page=10 \
  -H "Authorization: Bearer {admin_token}"
```

#### Filter by Category
```bash
curl -X GET "http://localhost:8000/api/admin/statistics/attempts?category_id=2&status=submitted" \
  -H "Authorization: Bearer {admin_token}"
```

### Filament Admin Panel

1. Log in as admin
2. Navigate to **Statistics** in the sidebar
3. View comprehensive analytics dashboard
4. Go to **Students** section to see individual student statistics
5. Click on a student to view detailed performance metrics

## Database Queries

The implementation uses efficient database queries:

1. **Indexed fields used**:
   - `quiz_attempts.status` (indexed)
   - `quiz_attempts.student_id` (FK, indexed)
   - `quiz_attempts.submitted_at` (indexed)
   - `quiz_attempts.started_at` (indexed)

2. **Join queries for grouped statistics**:
   - Category statistics use 3-table joins
   - Quiz statistics use 2-table joins
   - Properly optimized with grouping and aggregation

3. **Eager loading** in widgets and pages prevents N+1 queries

## Color Coding System

Performance is indicated by colors throughout the interface:

- **Green (success)**: ≥80% (Excellent)
- **Yellow (warning)**: 60-79% (Satisfactory)
- **Red (danger)**: <60% (Needs improvement)
- **Blue (info)**: Neutral information
- **Gray**: No data available

## Performance Considerations

1. **Caching**: Consider implementing caching for heavy calculations
   ```php
   Cache::remember('overall_statistics', 3600, function () {
       return $this->service->getOverallStatistics();
   });
   ```

2. **Pagination**: All list endpoints use pagination (default 20 per page)

3. **Database Indexes**: Ensure these are indexed for performance:
   - quiz_attempts.status
   - quiz_attempts.student_id
   - quiz_attempts.submitted_at
   - quizzes.category_id

## Future Enhancements

1. **Export functionality**: Export statistics to CSV/Excel
2. **Advanced filtering**: Date range filters in UI
3. **Trend analysis**: Track score improvements over time
4. **Alerts**: Notify admins of students with low performance
5. **Comparison tools**: Compare student groups or categories
6. **Custom reports**: Allow admins to create custom report queries
7. **Charts**: Add more chart types for visual analysis

## Troubleshooting

### No data appears in Statistics dashboard
1. Verify admin user has `role = 'admin'`
2. Check if quiz attempts exist in database
3. Verify attempts have `status = 'submitted'`

### Scores showing 0 or N/A
1. Check if Quiz_attempt model has `score_percent` field populated
2. Verify database migration was applied

### API endpoints return 401/403
1. Ensure authentication token is provided in header
2. Verify user account has admin role

## Files Modified/Created

**New Files**:
- `app/Services/QuizStatisticsService.php`
- `app/Filament/Pages/Statistics.php`
- `app/Filament/Widgets/StudentScoreStatsWidget.php`
- `resources/views/filament/pages/statistics.blade.php`
- `tests/Feature/QuizStatisticsServiceTest.php`
- `tests/Feature/AdminStatisticsApiTest.php`

**Modified Files**:
- `app/Models/User.php` (added quizAttempts relationship)
- `app/Filament/Resources/Students/StudentResource.php` (enhanced table)
- `app/Filament/Resources/Students/Pages/ViewStudent.php` (added StudentScoreStatsWidget)
- `routes/api.php` (added admin statistics routes)

## Requirements Met

✅ Identify the source of quiz scores in the database
- Used `quiz_attempts.score_percent` field

✅ Create backend query/service that computes:
- Overall average score of all students
- Average score per quiz
- Average score per student
- Total number of quiz attempts
- Highest score
- Lowest score

✅ Create backend API endpoint for statistics data
- Multiple endpoints with proper response formatting

✅ Ensure API returns properly formatted numeric values
- All scores are floats with 2 decimal places

✅ Add filters if needed:
- By quiz ✅
- By student ✅
- By date range ✅
- By category ✅

✅ Create admin dashboard section
- Dedicated Statistics page in Filament

✅ Display computed statistics clearly
- Multiple widgets with proper formatting

✅ Format values properly
- Percentages with 2 decimal places
- Color coding for performance levels

✅ Test using sample student attempts
- Comprehensive test suites included

✅ Handle empty-state cases
- Graceful handling throughout

✅ Admin can view average score statistics
- Dashboard and detailed views available

✅ Values are correct based on stored quiz results
- Tested with unit and feature tests

✅ Empty states do not break the page
- Verified in tests and implementation
