# Quiz Management System - Filament CRUD Implementation

This document describes the complete Filament CRUD implementation for Categories and Questions.

## Overview

The system provides a fully functional admin interface for managing quiz categories and questions with advanced features including:
- Conditional form fields based on question type
- Built-in validation rules
- Question preview functionality  
- Support for multiple question types (MCQ, True/False, Ordering, Short Answer)
- Filtering and search capabilities

## Navigation Structure

The admin panel includes:
- **Categories** - Manage quiz categories  
- **Questions** - Manage quiz questions for categories

## Categories Management

### Fields
- **Name** - Category name (required, max 255 characters)
- **Description** - Category description (optional)
- **Published** - Toggle to make category visible to students (defaults to true)

### Features
- **Create** - Add new categories
- **Edit** - Update existing categories
- **Delete** - Remove categories
- **Filter** - Filter by published status (Published/Unpublished/All)
- **Search** - Search by name
- **Sort** - Sort by name, publication status, or creation date

### Table Columns
- Category Name
- Description (truncated to 50 characters)
- Published status
- Number of questions in category
- Creation date

## Questions Management

### Question Types

#### 1. Multiple Choice (MCQ)
- **Options Needed**: Minimum 2 options
- **Correct Answers**: At least 1 (can be multiple)
- **Fields**: 
  - Option text for each choice
  - Checkbox to mark correct answer(s)

#### 2. True/False (TF)
- **Options Needed**: Exactly 2 options (True and False)
- **Correct Answers**: Exactly 1
- **Validation**: Enforces 2 options with 1 correct answer

#### 3. Ordering
- **Options Needed**: Minimum 2 items to order
- **Fields**:
  - Item text
  - Order index (position in correct sequence)
- **Use Case**: Students must arrange items in the correct sequence

#### 4. Short Answer
- **Answer Key Required**: Yes
- **Fields**:
  - Question prompt
  - Expected answer/rubric
- **Use Case**: Free-text student responses evaluated against rubric

### Question Fields
- **Category** - Select which category the question belongs to (required)
- **Question Type** - Choose type from dropdown (required)
- **Points** - Point value (required, minimum 1, defaults to 5)
- **Question Prompt** - The actual question text (required)
- **Options** - Conditional repeater based on type
- **Answer Key** - For short answer questions only

### Validation Rules

The system enforces the following validation rules:

#### MCQ Validation
- ✓ Must have at least 2 options
- ✓ Must have at least 1 correct answer

#### True/False Validation  
- ✓ Must have exactly 2 options
- ✓ Must have exactly 1 correct answer

#### Ordering Validation
- ✓ Must have at least 2 items
- ✓ Order indices work correctly

#### Short Answer Validation
- ✓ Must have an answer key/rubric
- ✓ Answer key cannot be empty

### Question Preview

The form includes a live preview section that shows:
- **MCQ**: Radio buttons with all options, highlighting the correct answer
- **True/False**: Radio buttons showing True/False with correct answer highlighted
- **Ordering**: Items displayed with their order numbers
- **Short Answer**: Expected answer/rubric in a read-only panel

The preview updates as you fill in the form and is only visible when the prompt is filled.

### Table Features

#### Columns
- Category (searchable, sortable)
- Question prompt (limit 50 chars, searchable)
- Question type (badged with color coding)
  - MCQ: Blue
  - True/False: Green
  - Ordering: Indigo
  - Short Answer: Orange
- Points value (numeric, sortable)
- Number of options
- Creation date (sortable)

#### Filters
- **Category** - Filter by category
- **Question Type** - Filter by question type

#### Actions
- **Edit** - Modify existing question
- **Delete** - Remove question
- **Bulk Delete** - Delete multiple questions

## Database Schema

### Categories Table
```sql
CREATE TABLE categories (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(255) NOT NULL,
    description LONGTEXT,
    is_published BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Questions Table
```sql
CREATE TABLE questions (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    category_id BIGINT NOT NULL,
    type ENUM('mcq', 'tf', 'ordering', 'short_answer') NOT NULL,
    prompt LONGTEXT NOT NULL,
    points INT NOT NULL,
    answer_key LONGTEXT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id)
);
```

### Question Options Table
```sql
CREATE TABLE question_options (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    question_id BIGINT NOT NULL,
    option_text VARCHAR(255) NOT NULL,
    is_correct BOOLEAN DEFAULT FALSE,
    order_index INT,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (question_id) REFERENCES questions(id)
);
```

## Models

### Category Model
Location: `app/Models/Category.php`

**Relationships**:
- `questions()` - HasMany relation to Question model

**Fillable Fields**:
- name
- description
- is_published

### Question Model
Location: `app/Models/Question.php`

**Relationships**:
- `category()` - BelongsTo relation to Category model
- `options()` - HasMany relation to QuestionOption model (ordered by order_index)

**Fillable Fields**:
- category_id
- type
- prompt
- points
- answer_key

**Methods**:
- `getValidationErrors()` - Returns array of validation errors for the question

### QuestionOption Model
Location: `app/Models/QuestionOption.php`

**Relationships**:
- `question()` - BelongsTo relation to Question model

**Fillable Fields**:
- question_id
- option_text
- is_correct
- order_index

**Casts**:
- is_correct → boolean

## Filament Resources

### CategoryResource
- **Location**: `app/Filament/Resources/Categories/CategoryResource.php`
- **Navigation Label**: Categories
- **Navigation Sort**: 1 (appears first)
- **Icon**: Rectangle Stack
- **Pages**: List, Create, Edit

### QuestionResource
- **Location**: `app/Filament/Resources/Questions/QuestionResource.php`
- **Navigation Label**: Questions
- **Navigation Sort**: 2 (appears second)
- **Icon**: Question Mark Circle
- **Pages**: List, Create, Edit

## Form Components

### CategoryForm
Location: `app/Filament/Resources/Categories/Schemas/CategoryForm.php`

Includes:
- Text input for name
- Textarea for description
- Toggle for is_published

### QuestionForm
Location: `app/Filament/Resources/Questions/Schemas/QuestionForm.php`

Features conditional rendering of components based on question type:
- Text input for category (relationship select)
- Select for question type with reactive updates
- Number input for points
- Textarea for question prompt
- Conditional repeater for options (MCQ/TF/Ordering)
- Conditional textarea for answer key (Short Answer)
- Preview component (always visible when prompt is filled)

## Usage Examples

### Create a Multiple Choice Question

1. Navigate to Questions → Create Question
2. Fill in the form:
   - Category: Select a category
   - Type: Choose "Multiple Choice (MCQ)"
   - Points: Enter 10
   - Prompt: Enter the question text
   - Options: Click "Add Option" and enter:
     - Option 1: "Option A" (not correct)
     - Option 2: "Option B" (correct) ✓
     - Option 3: "Option C" (not correct)
3. The preview updates showing selected options
4. Click Save

### Create a True/False Question

1. Navigate to Questions → Create Question
2. Fill in the form:
   - Category: Select a category
   - Type: Choose "True/False"
   - Prompt: Enter the statement
   - Options: System shows exactly 2 options
     - Option 1: "True"
     - Option 2: "False" (mark correct)
3. The system enforces exactly 2 options
4. Click Save

### Create an Ordering Question

1. Navigate to Questions → Create Question
2. Type: Choose "Ordering"
3. Add items in order:
   - Item 1: "First step" (Order: 1)
   - Item 2: "Second step" (Order: 2)
   - Item 3: "Third step" (Order: 3)
4. Preview shows items with order numbers
5. Click Save

### Create a Short Answer Question

1. Navigate to Questions → Create Question
2. Type: Choose "Short Answer"
3. Fill answer key with:
   - Expected correct answer
   - Key points students should include
   - Grading rubric/criteria
4. Click Save

## Key Features Implemented

✅ **Dynamic Form Fields**
- Form sections appear/disappear based on question type
- Repeater columns adjust based on type

✅ **Validation**
- MCQ: >=2 options, >=1 correct
- True/False: Exactly 2 options, exactly 1 correct
- Ordering: >=2 items
- Short Answer: Answer key required
- All validation shown in UI with helpful messages

✅ **Question Preview**
- Real-time preview of how question appears
- Different layouts for each question type
- Shows point value
- Highlights correct answers

✅ **Search and Filter**
- Search questions by prompt
- Filter by category and type
- Search categories by name
- Filter categories by publication status

✅ **User Experience**
- Helpful descriptions on each field
- Color-coded question types in table
- Ordered lists for options/items
- Responsive form layout
- Collapsible repeater items for organization

## Tips and Best Practices

1. **MCQ Questions**
   - Use clear, distinct options
   - Consider adding distractors that represent common misconceptions
   - You can mark multiple options as correct for multi-select questions

2. **True/False**
   - Keep statements concise
   - Avoid "always/never" absolutes
   - Use for concept verification

3. **Ordering**
   - Use for step-by-step processes
   - Make items distinct and clear
   - Order index should reflect correct sequence

4. **Short Answer**
   - Provide comprehensive rubric in answer key
   - Include acceptable variations in answers
   - Note common errors students make
   - Give point ranges for partial credit

5. **Categories**
   - Keep names short and descriptive
   - Use descriptions to clarify scope
   - Mark as published when ready for students

## Troubleshooting

### Validation errors on save
- Check that you have the minimum required options for your question type
- MCQ: Need >=2 options with >=1 correct
- True/False: Need exactly 2 options with 1 correct
- Ordering: Need >=2 items
- Short Answer: Need answer key filled in

### Preview not showing
- Make sure you've filled in the question prompt
- Preview only appears when prompt has content

### Options not saving
- Try collapsing and expanding the repeater
- Make sure you have required fields filled (option text)
- Check browser console for any JavaScript errors

## Files Modified/Created

### New Models
- `app/Models/QuestionOption.php`

### Updated Models
- `app/Models/Question.php`
- `app/Models/Category.php` (already existed, verified)

### Filament Resources
- `app/Filament/Resources/Categories/CategoryResource.php`
- `app/Filament/Resources/Categories/Pages/CreateCategory.php`
- `app/Filament/Resources/Categories/Pages/EditCategory.php`
- `app/Filament/Resources/Categories/Pages/ListCategories.php`
- `app/Filament/Resources/Categories/Schemas/CategoryForm.php`
- `app/Filament/Resources/Categories/Tables/CategoriesTable.php`
- `app/Filament/Resources/Questions/QuestionResource.php`
- `app/Filament/Resources/Questions/Pages/CreateQuestion.php`
- `app/Filament/Resources/Questions/Pages/EditQuestion.php`
- `app/Filament/Resources/Questions/Pages/ListQuestions.php`
- `app/Filament/Resources/Questions/Schemas/QuestionForm.php`
- `app/Filament/Resources/Questions/Tables/QuestionsTable.php`

### Views
- `resources/views/components/question-preview.blade.php`

