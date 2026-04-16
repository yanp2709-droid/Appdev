# Quiz Duration Consistency Fix

## Problem
When an admin creates a quiz with a specific duration in the admin panel, the student was not seeing the same duration when starting the quiz. The timer showed incorrect time or inconsistent values.

## Root Causes Identified

### 1. **Duration Not Consistent Between Quiz Settings and Attempt**
The frontend was receiving different duration values from different API endpoints:
- `quizSettingsPayload()` sent raw `$quiz->duration_minutes` without any fallback logic
- `attemptMeta()` calculated actual duration from `started_at` and `expires_at`
- If quiz had `duration_minutes = null` or `0`, the quiz settings showed null but the timer was actually using a calculated value

### 2. **Fallback Logic Not Applied to Frontend**
The backend had this logic for determining actual duration:
```php
$durationMinutes = (int) ($quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
if ($durationMinutes <= 0) {
    $durationMinutes = (int) ($category->time_limit_minutes ?? ...);
}
if ($durationMinutes <= 0) {
    $durationMinutes = self::DEFAULT_DURATION_MINUTES;
}
```

But the frontend didn't know about these fallbacks, so it showed wrong duration.

### 3. **Inconsistent Duration Calculation in History**
The history endpoint didn't apply the same fallback logic, showing inconsistent duration values.

---

## Solutions Implemented

### 1. **Unified Duration Calculation in quizSettingsPayload()**
**File:** [app/Http/Controllers/QuizAttemptController.php](app/Http/Controllers/QuizAttemptController.php#L834)

**Before:**
```php
'duration_minutes' => $quiz->duration_minutes,  // Could be null!
```

**After:**
```php
// Calculate the actual duration that will be used for the quiz attempt
$durationMinutes = (int) ($quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
if ($durationMinutes <= 0) {
    $category = Category::find($quiz->category_id);
    $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
}
if ($durationMinutes <= 0) {
    $durationMinutes = self::DEFAULT_DURATION_MINUTES;
}
// ... then return $durationMinutes
```

**Impact:** The frontend now receives the actual duration that will be used, matching what the admin set in the quiz.

---

### 2. **Consistent Duration in History Endpoint**
**File:** [app/Http/Controllers/QuizAttemptController.php](app/Http/Controllers/QuizAttemptController.php#L186)

**Before:**
```php
$durationMinutes = $attempt->getDurationMinutes();
if ($durationMinutes === null) {
    $durationMinutes = (int) ($attempt->quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
    // No fallback for category or default if duration is 0
}
```

**After:**
```php
$durationMinutes = $attempt->getDurationMinutes();
if ($durationMinutes === null) {
    $durationMinutes = (int) ($attempt->quiz->duration_minutes ?? self::DEFAULT_DURATION_MINUTES);
    if ($durationMinutes <= 0) {
        $category = $attempt->quiz->category;
        $durationMinutes = (int) ($category->time_limit_minutes ?? self::DEFAULT_DURATION_MINUTES);
    }
    if ($durationMinutes <= 0) {
        $durationMinutes = self::DEFAULT_DURATION_MINUTES;
    }
}
```

**Impact:** Attempt history now shows consistent, correct duration values.

---

### 3. **Related Previous Fixes**
From the previous timeout fix:
- ✅ Fixed `diffInSeconds()` calculation direction ([Quiz_attempt.php](app/Models/Quiz_attempt.php#L91) & [QuizAttemptController.php](app/Http/Controllers/QuizAttemptController.php#L537))
- ✅ Fixed `timer_enabled` default to `true` ([Quiz.php](app/Models/Quiz.php#L107))
- ✅ Added safety check for duration > 0 before calculating expiration ([QuizAttemptController.php](app/Http/Controllers/QuizAttemptController.php#L125))

---

## How It Works Now

### Timeline Example
**Admin Panel:**
1. Admin creates Quiz: "Math Quiz"
2. Sets: Category = "Math", Duration = **20 minutes**, Timer Enabled = **Yes**
3. Quiz saved to database with `duration_minutes = 20`

**Student App:**
1. Student navigates to take quiz
2. Student calls: `POST /api/quiz/attempt` with `quiz_id = 1`

**Backend Processing:**
```
1. Fetch quiz from database
   → duration_minutes = 20
   
2. In attempt() method:
   → Calculate durationMinutes = 20
   → Create expiration: now + 20 minutes = expires_at
   → Create Quiz_attempt with expires_at set
   
3. In buildAttemptPayload():
   → quizSettingsPayload() calculates duration = 20
   → attemptMeta() calculates duration = 20 (from started_at to expires_at)
   → Send both to frontend
   
4. Frontend receives:
   quiz_settings.duration_minutes = 20
   attempt.duration_minutes = 20
   attempt.remaining_seconds = ~1200 (20 minutes)
```

**Frontend Display:**
- Shows timer: **20:00** (20 minutes)
- Countdown works correctly
- Matches what admin set

---

## Deployment Steps

### 1. Pull Latest Code
All changes have been implemented in the codebase.

### 2. Test the Fix

**Scenario A: Quiz with explicit duration**
```bash
# Admin creates: Math Quiz with 20 minutes
# Student starts the quiz
# Verify: Timer shows 20:00
```

**Scenario B: Quiz with category fallback**
```bash
# Admin creates: Quiz with NO duration set
# Category has: 15 minutes time_limit
# Student starts the quiz
# Verify: Timer shows 15:00 (from category)
```

**Scenario C: Quiz with default fallback**
```bash
# Admin creates: Quiz with NO duration, category has NO time_limit
# Student starts the quiz
# Verify: Timer shows 10:00 (default)
```

### 3. Clear Cache
```bash
php artisan cache:clear
```

---

## What Changed

| Component | Change | Reason |
|-----------|--------|--------|
| quizSettingsPayload() | Now applies fallback logic | Frontend sees actual duration |
| history() endpoint | Now applies fallback logic | Consistent duration in history |
| timer_enabled default | Changed to true | Matches database default |
| remainingSeconds() | Fixed calculation direction | Shows positive remaining time |
| Attempt creation | Added duration > 0 check | Prevents invalid expiration times |

---

## Testing Recommendations

### Manual Testing
1. Create quiz with 15 minute duration
2. Start the quiz
3. Check API response: `curl -H "Authorization: Bearer TOKEN" http://localhost:8000/api/quiz/attempt -d '{"quiz_id": 1}'`
4. Verify in response:
   - `data.quiz_settings.duration_minutes: 15`
   - `data.attempt.duration_minutes: 15`
   - `data.attempt.remaining_seconds: ~900`

### Automated Testing
```bash
php artisan test tests/Feature/QuizAttemptTest.php -v
```

---

## FAQ

### Q: What if admin doesn't set a duration?
**A:** The system will use the category's `time_limit_minutes`. If that's also not set, it uses default 10 minutes.

### Q: Does this affect existing quiz attempts?
**A:** No. Only new attempts created after the fix will show correct duration. Existing attempts' duration is calculated from their `started_at` and `expires_at` times, which don't change.

### Q: What if quiz has `timer_enabled = false`?
**A:** The timer won't be set (expires_at = null), and `remaining_seconds` will be 0. The frontend should handle this by disabling the timer display.

### Q: Why are there two duration values in the API response?
**A:** 
- `attempt.duration_minutes` = actual timer duration (for countdown)
- `quiz_settings.duration_minutes` = quiz configuration (for display)

Both should now be identical after this fix.

---

## Files Modified

1. [app/Http/Controllers/QuizAttemptController.php](app/Http/Controllers/QuizAttemptController.php)
   - Updated `quizSettingsPayload()` method
   - Updated `history()` method
   - Updated `remainingSeconds()` method (from timeout fix)
   - Updated `attempt()` method (from timeout fix)

2. [app/Models/Quiz.php](app/Models/Quiz.php)
   - Updated default for `timer_enabled` (from timeout fix)

3. [app/Models/Quiz_attempt.php](app/Models/Quiz_attempt.php)
   - Updated `getRemainingSeconds()` method (from timeout fix)

---

## Related Issues Fixed
- ✅ Quiz Timeout Issue (04-16-2026)
- ✅ Quiz Duration Consistency (this fix)

---

**Fixed by:** GitHub Copilot
**Date:** April 16, 2026
**Version:** 1.0
