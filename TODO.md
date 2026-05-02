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

