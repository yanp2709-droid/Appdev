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

# Task 4: Display Quiz History in Flutter Per Quiz Instead of General History
...existing code...

# Task: Implement Teacher Profile Management with IT-Focused Professional Information

## Description
Add a teacher profile feature in the system that allows displaying detailed professional information related to the subjects and expertise of each teacher. The profile should present relevant academic and IT-related details in a structured and visually organized way to make profiles more informative and professional.

This feature will help users easily identify teacher qualifications, areas of specialization, and teaching responsibilities while improving the overall presentation of faculty information.

## Objective
To create a more informative and presentable teacher profile section that highlights teaching roles, IT expertise, and professional details.

## Scope / Functional Requirements
- Create a dedicated teacher profile section/page.
- Allow the profile to display:
	- Full name
	- Profile picture
	- Position/role
	- Subjects currently teaching
	- Area of IT specialization
	- Educational background
	- Skills and technologies
	- Certifications
	- Years of teaching experience
	- Professional summary/about section
	- Contact information (if applicable)
	- Office schedule/consultation hours
	- Department or program affiliation
- Add IT-related information such as:
	- Programming languages known
	- Frameworks/tools used
	- Database experience
	- Software expertise
	- Research interests
	- Current projects (optional)
	- Achievements or awards
	- Portfolio/GitHub/LinkedIn links (if available)
- Improve profile presentation through:
	- organized sections/cards
	- profile banner/header
	- icons for skills and technologies
	- responsive design for different screen sizes
- Allow profile information updates through an admin or authorized teacher account.
- Provide validation for required profile fields.
- Ensure profiles load correctly without affecting existing system performance.

## Suggested Profile Sections
- Basic Information
- Teaching Information
- IT Skills & Technologies
- Academic Background
- Professional Experience
- Certifications & Achievements
- Projects and Research
- Contact Details

## Technical Notes / Steps
1. Review existing user and teacher database structure.
2. Create additional database fields for teacher professional information if needed.
3. Design profile UI layout with a clean and modern structure.
4. Implement profile image upload functionality.
5. Create backend APIs for:
		- retrieving teacher profiles
		- updating profile information
		- managing profile details
6. Add frontend components for:
		- profile cards
		- skills/tags display
		- teacher information sections
7. Implement form validation for profile updates.
8. Ensure responsive behavior for desktop, tablet, and mobile devices.
9. Test profile loading and display performance.

## Acceptance Criteria
- Teacher profiles display complete professional and teaching information.
- Subjects being taught are clearly shown.
- IT-related skills and expertise are properly presented.
- Profile layout is organized and visually appealing.
- Profile information can be updated successfully by authorized users.
- Profile images and links load correctly.
- Responsive design works across different screen sizes.
- Existing system functionality remains stable after implementation.
- [ ] Ensure backend/API supports quiz-based history (quiz_id, quiz title, etc.)
- [ ] Update Flutter UI to group/filter history by quiz
- [ ] Display quiz title, date, score, status, etc. per record
- [ ] Test with multiple quizzes, multiple attempts, no history, etc.
- [ ] Acceptance: History is clear, grouped by quiz, accurate, and UI is easy to navigate

