# TechQuiz ERD (Updated)

Schema basis: Laravel migrations in `laravel-12.x/database/migrations` through `2026_05_10_000000`.

## Entities

### users
- PK `id`
- `name`, `email` (UQ), `password`, `role`, `student_id` (UQ nullable)
- `first_name`, `last_name`, `section`, `year_level`, `course`
- `privacy_consent`, `is_protected`, `is_active`
- `school_year`, `academic_year`, timestamps

### categories
- PK `id`
- `name` (UQ), `description`, `is_published`, `time_limit_minutes`, timestamps

### quizzes
- PK `id`
- FK `category_id -> categories.id` (required)
- FK `teacher_id -> users.id` (nullable)
- `title`, `difficulty`, `duration_minutes`
- `timer_enabled`, `shuffle_questions`, `shuffle_options`, `max_attempts`
- `show_score_immediately`, `show_answers_after_submit`
- `allow_review_before_submit`, `show_correct_answers_after_submit`, `is_active`, timestamps

### questions
- PK `id`
- FK `category_id -> categories.id` (required)
- FK `quiz_id -> quizzes.id` (nullable)
- `question_type`, `question_text`, `points`, `answer_key`, timestamps

### question_options
- PK `id`
- FK `question_id -> questions.id`
- `option_text`, `is_correct`, `order_index`, timestamps

### answers
- PK `id`
- FK `question_id -> questions.id`
- `answer_text`, `is_correct`, timestamps

### quiz_attempts
- PK `id`
- FK `student_id -> users.id`
- FK `quiz_id -> quizzes.id`
- FK `last_viewed_question_id -> questions.id` (nullable)
- `attempt_type`, `school_year`, `score`, `status`
- `started_at`, `expires_at`, `submitted_at`, `completed_at`
- `total_items`, `answered_count`, `correct_answers`, `score_percent`
- `question_sequence`, `last_activity_at`, `last_viewed_question_index`, timestamps

### attempt_answers
- PK `id`
- FK `quiz_attempt_id -> quiz_attempts.id`
- FK `question_id -> questions.id`
- FK `answer_id -> answers.id` (nullable)
- FK `question_option_id -> question_options.id` (nullable)
- `text_answer`, `selected_option_ids`, `is_correct`, `is_bookmarked`, timestamps
- UQ (`quiz_attempt_id`, `question_id`)

### quiz_retake_allowances
- PK `id`
- FK `student_id -> users.id`
- FK `quiz_id -> quizzes.id`
- FK `updated_by -> users.id` (nullable)
- `additional_graded_attempts`, timestamps
- UQ (`student_id`, `quiz_id`)

### dashboard_widgets
- PK `id`
- FK `user_id -> users.id`
- `widget_class`, `widget_name`, `order`, `is_visible`, `settings`, timestamps
- UQ (`user_id`, `widget_class`)

## Relationship Summary (Cardinality)

- `categories` 1 -> many `quizzes`
- `users` (teacher) 1 -> many `quizzes` (nullable on quiz)
- `categories` 1 -> many `questions`
- `quizzes` 1 -> many `questions` (nullable FK on question)
- `questions` 1 -> many `question_options`
- `questions` 1 -> many `answers`
- `users` (student) 1 -> many `quiz_attempts`
- `quizzes` 1 -> many `quiz_attempts`
- `questions` 1 -> many `quiz_attempts.last_viewed_question_id` references (optional)
- `quiz_attempts` 1 -> many `attempt_answers`
- `questions` 1 -> many `attempt_answers`
- `answers` 1 -> many `attempt_answers` (optional FK at child)
- `question_options` 1 -> many `attempt_answers` (optional FK at child)
- `users` 1 -> many `quiz_retake_allowances` as `student_id`
- `quizzes` 1 -> many `quiz_retake_allowances`
- `users` 1 -> many `quiz_retake_allowances` as `updated_by` (nullable)
- `users` 1 -> many `dashboard_widgets`
