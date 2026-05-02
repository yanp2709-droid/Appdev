# Validation Error Check Implementation

## Plan
Implement validation error checks for question creation/editing so users get clear reminders when:
- Required textboxes are not filled
- The "Correct" checkbox is not checked for applicable question types
- Validation ONLY affects the question type section being submitted

## Steps

1. **QuestionForm.php** – Add section-specific validation
   - [x] Removed `->required()` from section-level fields (`category_id`, `points`, `question_text`, `answer_key`) to prevent cross-section validation errors
   - [x] Kept `->required()` on `option_text` inside Repeater (only affects items within that specific repeater)
   - [x] Added custom `->rules()` closures on options Repeater for:
     - True/False: exactly 2 options, exactly 1 correct
     - Multiple Choice: at least 2 options, exactly 1 correct
     - Multi-select: at least 2 options, at least 1 correct

2. **CreateQuestion.php** – Improve error mapping granularity in `mutateFormDataBeforeCreate()`
   - [x] Map empty option text errors to specific repeater indices: `{section}.options.{index}.option_text`
   - [x] Map duplicate option errors to offending option index
   - [x] Keep correct-count / option-count errors at repeater level `{section}.options`

3. **EditQuestion.php** – Apply same granular error mapping in `mutateFormDataBeforeSave()`
   - [x] Mirror all CreateQuestion error mapping improvements

## Behavior
- When clicking **Create** on True/False, only True/False fields are validated
- No validation errors appear from Multiple Choice, Multi-select, or Short Answer sections
- Empty textboxes and unchecked "Correct" options show precise field-level error messages

