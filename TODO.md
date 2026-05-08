# TODO — Admin Score as Number + Profile Tab Separation

## Task 1: Admin score must be raw number, not average/percentage
- [ ] `app/Filament/Resources/Attempts/AttemptResource.php` — Replace `score_percent` with `correct_answers/total_items`
- [ ] `app/Filament/Widgets/RecentAttemptsWidget.php` — Replace `score_percent` with `correct_answers/total_items`
- [ ] `app/Filament/Widgets/StudentStatsWidget.php` — Replace "Avg Score" with "Total Correct" (sum of correct_answers)
- [ ] `resources/views/filament/resources/students/quiz-attempt-details.blade.php` — Replace `score_percent %` with `score / total_items`
- [ ] `resources/views/filament/widgets/student-attempt-history-modal.blade.php` — Replace `score_percent %` with `score / total_items`

## Task 2: Separate staff and students in Edit Profile
- [x] `app/Filament/Pages/EditProfile.php` — Add `$activeTab`, `getStaffUsers()`, `getStudentUsers()`, `switchTab()`
- [x] `resources/views/filament/pages/edit-profile.blade.php` — Add tab buttons and filter user list by active tab

## Task 3: Round Score Percentage to Whole Number
- [ ] Update all score percentage displays to show whole numbers only (no decimals), using standard rounding (e.g., 87.5% → 88%). Applies to quiz results, score summaries, analytics, reports, dashboard cards, and student records. % symbol must still be shown. Backend calculations may keep decimals, but all user-facing displays must be rounded.
- [ ] Identify all locations (backend and frontend) where score percentages are displayed
- [ ] Apply standard rounding and update formatting in all relevant views/components/pages
- [ ] Ensure reports and analytics use the same rule
- [ ] Test edge cases (e.g., .4, .5, .9, 0%, 100%)
- [ ] Acceptance: No decimals shown, rounding is consistent, % symbol present

## Task 4: Display Quiz History in Flutter Per Quiz Instead of General History
- [ ] Update the Flutter app so quiz history is shown per quiz, not as a general mixed list. Users should be able to view history for a selected quiz only. If the backend/API does not yet support quiz-specific history, update it to allow filtering/grouping by quiz.
- [ ] Review current Flutter history screen and data fetching
- [ ] Ensure backend/API supports quiz-based history (quiz_id, quiz title, etc.)
- [ ] Update Flutter UI to group/filter history by quiz
- [ ] Display quiz title, date, score, status, etc. per record
- [ ] Test with multiple quizzes, multiple attempts, no history, etc.
- [ ] Acceptance: History is clear, grouped by quiz, accurate, and UI is easy to navigate

