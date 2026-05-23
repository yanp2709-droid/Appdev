# Normalization (Updated)

## Data Dictionary

| Table Name | Column Name | Data Type |
|---|---|---|
| users | id | BIGINT PK |
|  | name | VARCHAR |
|  | email | VARCHAR UNIQUE |
|  | role | ENUM(admin, teacher, student) |
|  | student_id | VARCHAR UNIQUE NULL |
|  | academic_year | VARCHAR NULL |
|  | created_at | TIMESTAMP |
|  | updated_at | TIMESTAMP |
| categories | id | BIGINT PK |
|  | name | VARCHAR UNIQUE |
|  | description | TEXT NULL |
|  | is_published | BOOLEAN |
|  | time_limit_minutes | UNSIGNED INT |
| quizzes | id | BIGINT PK |
|  | title | VARCHAR |
|  | category_id | BIGINT FK |
|  | teacher_id | BIGINT FK NULL |
|  | difficulty | ENUM(Easy, Medium, Hard) |
|  | duration_minutes | UNSIGNED INT |
|  | is_active | BOOLEAN |
| questions | id | BIGINT PK |
|  | quiz_id | BIGINT FK NULL |
|  | category_id | BIGINT FK |
|  | question_text | TEXT/LONGTEXT |
|  | question_type | ENUM(mcq, tf, multi_select, ordering, short_answer) |
|  | points | INT |
| question_options | id | BIGINT PK |
|  | question_id | BIGINT FK |
|  | option_text | VARCHAR |
|  | is_correct | BOOLEAN |
|  | order_index | INT NULL |
| answers | id | BIGINT PK |
|  | question_id | BIGINT FK |
|  | answer_text | VARCHAR |
|  | is_correct | BOOLEAN |
| quiz_attempts | id | BIGINT PK |
|  | student_id | BIGINT FK |
|  | quiz_id | BIGINT FK |
|  | status | VARCHAR |
|  | score_percent | DECIMAL(5,2) |
|  | attempt_type | VARCHAR |
| attempt_answers | id | BIGINT PK |
|  | quiz_attempt_id | BIGINT FK |
|  | question_id | BIGINT FK |
|  | answer_id | BIGINT FK NULL |
|  | question_option_id | BIGINT FK NULL |
|  | is_correct | BOOLEAN NULL |
|  | UNIQUE(quiz_attempt_id, question_id) | CONSTRAINT |

## Normalization Summary

- 1NF: Atomic fields are used for core entities; JSON fields are only for variable payloads.
- 2NF: Multi-key facts are isolated in bridge tables (`attempt_answers`, `quiz_retake_allowances`).
- 3NF: Entity data is separated (`users`, `quizzes`, `questions`, `attempts`); scoring aggregates are intentional denormalization for performance.

## Notes

- `questions` keeps both `category_id` and `quiz_id`; consistency should be enforced in app logic.
- `answers` and `question_options` overlap for correctness storage and should follow a clear write-path policy.