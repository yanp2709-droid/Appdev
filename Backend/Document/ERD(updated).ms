.TL
TechQuiz ERD (Updated)
.AU
ApplicationDevelopment Backend
.DA
May 19, 2026
.PP
Schema basis: Laravel migrations in laravel-12.x/database/migrations through 2026_05_10_000000.
.SH
Core Tables
.PP
users(id PK, email UQ, student_id UQ nullable, role, school_year, academic_year, is_active, is_protected, ...)
.PP
categories(id PK, name UQ, is_published, time_limit_minutes, ...)
.PP
quizzes(id PK, category_id FK required, teacher_id FK nullable, difficulty, duration_minutes, timer/review flags, is_active, ...)
.PP
questions(id PK, category_id FK required, quiz_id FK nullable, question_type, question_text, points, answer_key)
.PP
question_options(id PK, question_id FK, option_text, is_correct, order_index)
.PP
answers(id PK, question_id FK, answer_text, is_correct)
.PP
quiz_attempts(id PK, student_id FK, quiz_id FK, last_viewed_question_id FK nullable,
attempt_type, school_year, score/status/timestamps, totals/percent, question_sequence, ...)
.PP
attempt_answers(id PK, quiz_attempt_id FK, question_id FK, answer_id FK nullable,
question_option_id FK nullable, text_answer, selected_option_ids, is_correct, is_bookmarked,
UNIQUE(quiz_attempt_id, question_id))
.PP
quiz_retake_allowances(id PK, student_id FK, quiz_id FK, updated_by FK nullable,
additional_graded_attempts, UNIQUE(student_id, quiz_id))
.PP
dashboard_widgets(id PK, user_id FK, widget_class, widget_name, is_visible,
settings, UNIQUE(user_id, widget_class))
.SH
Cardinality
.PP
categories 1:N quizzes
.br
users(teacher) 1:N quizzes [nullable FK on quizzes.teacher_id]
.br
categories 1:N questions
.br
quizzes 1:N questions [nullable FK on questions.quiz_id]
.br
questions 1:N question_options
.br
questions 1:N answers
.br
users(student) 1:N quiz_attempts
.br
quizzes 1:N quiz_attempts
.br
questions 1:N quiz_attempts via last_viewed_question_id (optional)
.br
quiz_attempts 1:N attempt_answers
.br
questions 1:N attempt_answers
.br
answers 1:N attempt_answers (optional child FK)
.br
question_options 1:N attempt_answers (optional child FK)
.br
users 1:N quiz_retake_allowances via student_id
.br
quizzes 1:N quiz_retake_allowances
.br
users 1:N quiz_retake_allowances via updated_by (optional)
.br
users 1:N dashboard_widgets
.SH
Normalization Snapshot
.PP
1NF: Core facts are atomic; JSON fields are used intentionally for variable payloads.
.br
2NF: Multi-key facts are isolated in attempt_answers and quiz_retake_allowances.
.br
3NF: Core entities are separated; quiz_attempt scoring aggregates are deliberate performance denormalizations.
