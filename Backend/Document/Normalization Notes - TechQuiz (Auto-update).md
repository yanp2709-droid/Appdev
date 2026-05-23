# Normalization & ERD Update Notes (TechQuiz)

This document reflects the latest schema state from Laravel migrations under `laravel-12.x/database/migrations` (up to `2026_05_10_000000`).

## 1) Final core entities (application domain)

### users
- PK: `id`
- Unique: `email`, `student_id` (nullable unique)
- Key attributes: `role` (`admin|teacher|student`), `is_protected`, `is_active`, `school_year`, `academic_year`
- Notes: combines auth + student/teacher profile fields.

### categories
- PK: `id`
- Unique: `name`
- Key attributes: `description`, `is_published`, `time_limit_minutes`

### quizzes
- PK: `id`
- FK: `category_id -> categories.id` (required), `teacher_id -> users.id` (nullable; cascade on delete)
- Key attributes: `difficulty`, `duration_minutes`, timer/review/visibility flags, `max_attempts`, `is_active`

### questions
- PK: `id`
- FK: `category_id -> categories.id` (required), `quiz_id -> quizzes.id` (nullable, null on delete)
- Key attributes: `question_type`, `question_text`, `points`, `answer_key`

### question_options
- PK: `id`
- FK: `question_id -> questions.id` (required)
- Key attributes: `option_text`, `is_correct`, `order_index`

### answers (legacy answer key table)
- PK: `id`
- FK: `question_id -> questions.id` (required)
- Key attributes: `answer_text`, `is_correct`
- Note: still present; overlaps conceptually with `question_options` for correctness metadata.

### quiz_attempts
- PK: `id`
- FK: `student_id -> users.id`, `quiz_id -> quizzes.id`, `last_viewed_question_id -> questions.id` (nullable)
- Key attributes:
  - scoring aggregates: `score`, `total_items`, `answered_count`, `correct_answers`, `score_percent`
  - state/timing: `status`, `started_at`, `expires_at`, `submitted_at`, `completed_at`, `last_activity_at`
  - resume/support: `question_sequence` (JSON), `last_viewed_question_index`
  - categorization: `attempt_type`, `school_year`

### attempt_answers
- PK: `id`
- FK: `quiz_attempt_id -> quiz_attempts.id`, `question_id -> questions.id`, `answer_id -> answers.id` (nullable), `question_option_id -> question_options.id` (nullable)
- Unique: `UNIQUE(quiz_attempt_id, question_id)`
- Key attributes: `text_answer`, `selected_option_ids` (JSON), `is_correct`, `is_bookmarked`

### quiz_retake_allowances
- PK: `id`
- FK: `student_id -> users.id`, `quiz_id -> quizzes.id`, `updated_by -> users.id` (nullable)
- Unique: `UNIQUE(student_id, quiz_id)`
- Key attribute: `additional_graded_attempts`

### dashboard_widgets
- PK: `id`
- FK: `user_id -> users.id`
- Unique: `UNIQUE(user_id, widget_class)`
- Key attributes: `widget_name`, `order`, `is_visible`, `settings` (JSON)

## 2) Normalization assessment

- **1NF:** Domain data is primarily atomic. JSON columns are intentionally used for variable/ordered payloads (`question_sequence`, `selected_option_ids`, widget `settings`) and not for core reference entities.
- **2NF:** Multi-key business facts are modeled in bridge/fact tables (`attempt_answers`, `quiz_retake_allowances`) with full-key dependency.
- **3NF:** Major transitive dependencies are separated by entity boundaries (`users`, `quizzes`, `questions`, `attempt_*`). Derived scoring summaries in `quiz_attempts` are deliberate denormalizations for performance.

## 3) Current design notes

1. `questions` contains both `category_id` and `quiz_id`. If quizzes are permanently bound to one category, `category_id` may become redundant and should be kept synchronized by application rules.
2. `answers` and `question_options` both store correctness concepts. This is workable but should be treated as intentional dual-path support (legacy vs modern question types), with clear write-path ownership in services.
3. Multiple year fields exist (`school_year`, `academic_year`) in `users`, and `school_year` in `quiz_attempts`. This supports reporting/history but requires consistent semantics in analytics queries.

## 4) ERD sync status

- ERD relationships and constraints have been regenerated to align with the migration-defined final schema state.
- Target artifact: `Document/ERD(updated).pdf`
