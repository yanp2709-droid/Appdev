# TODO.md - Quiz Flow Update: Subject > Quizzes > Questions

## Task Overview
Update the quiz-taking flow to support the new backend structure where:
- Each **Subject** can have multiple **Quizzes**
- Each **Quiz** belongs to exactly one **Subject**
- Questions are retrieved based on the selected Quiz, not directly from Subject

## Current Structure
- **CategoriesScreen**: Shows list of categories (treated as subjects)
- **QuizProvider.startQuiz(categoryId)**: Gets questions from category
- Single quiz per category (Subject = Quiz)

## New Structure
- **Subject list**: Browse all subjects
- **Quiz list**: Show all quizzes for a selected subject  
- **Quiz detail**: Take quiz and load questions from quiz

---

## Implementation Steps

### Phase 1: Data Models

#### 1.1 Create Quiz Model ✅ COMPLETED
- [x] **File**: `lib/features/quiz/data/models/quiz.dart`
- [x] **Fields**:
  - `id`: int
  - `subjectId`: int (foreign key)
  - `title`: String
  - `description`: String?
  - `isActive`: bool
  - `questionCount`: int
  - `timeLimit`: int (minutes)
  - `createdAt`: DateTime?
  - `updatedAt`: DateTime?
- [x] **Methods**: `fromJson()`, `toJson()`

#### 1.2 Update Subject Model (reuse or extend CategoryModel) ✅ COMPLETED
- [x] Keep `SubjectModel` as alias/rebrand of CategoryModel
- [x] May add new fields if needed

---

### Phase 2: Services ✅ COMPLETED

#### 2.1 Create Quiz Service ✅ COMPLETED
- [x] **File**: `lib/services/quiz_list_service.dart`
- [x] **Methods**:
  - `getQuizzesBySubject(int subjectId)`: Fetch quizzes for subject
  - `getQuizDetails(int quizId)`: Get quiz with questions
  - `getQuizAvailability(int quizId)`: Check if quiz is active

#### 2.2 Update Categories Service (rename to Subjects) ✅ COMPLETED
- [x] Reuses existing CategoriesService
- [x] Keep existing functionality for backward compatibility

---

### Phase 3: Providers ✅ COMPLETED

#### 3.1 Create QuizListProvider ✅ COMPLETED
- [x] **File**: `lib/features/quiz/providers/quiz_list_provider.dart`
- [x] **State**:
  - `QuizzesStatus`: initial, loading, success, error, empty
  - `List<QuizModel> quizzes`
  - `String? errorMessage`
- [x] **Methods**:
  - `fetchQuizzes(int subjectId)`: Load quizzes for subject
  - `selectQuiz(QuizModel quiz)`: Set current quiz

---

### Phase 4: Screens ✅ COMPLETED

#### 4.1 Rename CategoriesScreen -> SubjectsScreen - PARTIALLY COMPLETED
- [x] **File**: `lib/features/categories/presentation/screens/categories_screen.dart`
- [x] Keeps similar UI to old CategoriesScreen
- [x] Navigate to QuizListScreen on subject tap
- [ ] Could rename to subjects_screen.dart later

#### 4.2 Create QuizListScreen ✅ COMPLETED
- [x] **File**: `lib/features/quiz/presentation/screens/quiz_list_screen.dart`
- [x] **Features**:
  - Show quizzes for selected subject
  - Display quiz title, description, question count, time limit
  - Show active/inactive status
  - **Empty state**: "No quizzes available for this subject"
  - **Inactive quiz**: Show as disabled or unavailable

#### 4.3 Update QuizScreen ✅ COMPLETED
- [x] **File**: `lib/features/quiz/presentation/screens/quiz_screen.dart`
- [x] Update to load questions from quiz based on `quizId`
- [x] Keep existing quiz-taking UI (reuse)

---

### Phase 5: Routing ✅ COMPLETED

#### 5.1 Update App Router ✅ COMPLETED
- [x] **File**: `lib/app/app_router.dart`
- [x] **Routes**:
  - `/categories` -> CategoriesScreen (Subjects list)
  - `/subjects/:subjectId` -> QuizListScreen (quizzes for subject) ✅ NEW
- [x] Update navigation flow with proper back navigation

---

### Phase 6: Provider Updates ✅ COMPLETED

#### 6.1 Update QuizProvider ✅ COMPLETED
- [x] **File**: `lib/features/quiz/providers/quiz_provider.dart`
- [x] **Changes**:
  - Add `int? currentQuizId`
  - Add `String? currentQuizTitle`
  - Add `startQuizWithQuiz()` method
  - Update `startQuiz()` to accept `quizId` and fetch quiz-specific questions
  - Keep backward compatibility with `startQuiz(categoryId)` for existing flow

#### 6.2 Update QuizAttemptService ✅ COMPLETED (Already had quizId support)
- [x] **File**: `lib/services/quiz_attempt_service.dart`
- [x] **Changes**:
  - Already supports `quiz_id` parameter
  - Support both category-based and quiz-based attempts

---

### Phase 7: UI/UX Updates - PARTIALLY COMPLETED

#### 7.1 Update Student Home Screen ⚠️ NEEDS UPDATE
- [ ] **File**: `lib/features/home/student/student_home_screen.dart`
- [ ] **Changes**:
  - Update navigation texts: "Browse Categories" -> "Browse Subjects" (optional)
  - Update drawer menu

#### 7.2 Update Navigation Flow ✅ COMPLETED
- [x] Students see Subject list (CategoriesScreen)
- [x] Tap Subject -> Navigate to Quiz list (QuizListScreen)
- [x] Tap Quiz -> Navigate to Quiz taking screen (QuizScreen)
- [x] Back from Quiz -> Returns to Quiz list
- [x] Back from Quiz list -> Returns to Subject list

---

## Files Created/Updated

### Created:
1. ✅ `lib/features/quiz/data/models/quiz.dart` - Quiz model
2. ✅ `lib/features/quiz/providers/quiz_list_provider.dart` - Quiz list state management
3. ✅ `lib/features/quiz/presentation/screens/quiz_list_screen.dart` - Quiz list UI
4. ✅ `lib/services/quiz_list_service.dart` - Quiz list API service

### Updated:
1. ✅ `lib/app/app_router.dart` - Add new routes
2. ✅ `lib/features/quiz/providers/quiz_provider.dart` - Add quiz support
3. ✅ `lib/features/categories/presentation/screens/categories_screen.dart` - Navigate to QuizListScreen
4. ✅ `lib/services/quiz_attempt_service.dart` - Already supports quiz_id

---

## Remaining Tasks

1. ⚠️ Update Student Home Screen labels (optional - can keep "Categories")
2. ⚠️ Test full navigation flow
3. ⚠️ Verify API endpoints are correct

---

## New Navigation Flow

```
StudentHomeScreen
    |
    v
CategoriesScreen (shows subjects/categories)
    |
    v (tap a subject)
QuizListScreen (shows quizzes for selected subject)
    |
    v (tap a quiz)
QuizScreen (takes quiz with quiz-specific questions)
    |
    v (finish)
QuizResultScreen
```

---

## How It Works Now

1. **CategoriesScreen**: Students browse subjects (formerly called categories)
2. **Tap Subject**: Navigates to `/subjects/{subjectId}?name={subjectName}`
3. **QuizListScreen**: Shows all quizzes for that subject
4. **Tap Quiz**: Calls `QuizProvider.startQuizWithQuiz(quizId, ...)` which fetches questions for that specific quiz
5. **QuizScreen**: Shows quiz questions

---

*Updated: Quiz Flow implementation completed*
