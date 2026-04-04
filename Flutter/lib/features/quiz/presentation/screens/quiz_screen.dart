// lib/features/quiz/presentation/screens/quiz_screen.dart

import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../providers/quiz_provider.dart';

class QuizScreen extends StatefulWidget {
  const QuizScreen({super.key});

  @override
  State<QuizScreen> createState() => _QuizScreenState();
}

class _QuizScreenState extends State<QuizScreen> with WidgetsBindingObserver {
  bool _showUnansweredWarning = false;
  final TextEditingController _textController = TextEditingController();
  int? _lastQuestionId;
  bool _isSubmitting = false;

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addObserver(this);
  }

  @override
  void didChangeAppLifecycleState(AppLifecycleState state) {
    if (state == AppLifecycleState.resumed) {
      final quiz = context.read<QuizProvider>();
      if (quiz.status == QuizStatus.active) {
        quiz.refreshAttemptStatus();
      }
    }
  }

  @override
  void dispose() {
    WidgetsBinding.instance.removeObserver(this);
    _textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final quiz = context.watch<QuizProvider>();

    // ── LOADING ──────────────────────────────────────────────
    if (quiz.status == QuizStatus.loading) {
      return Scaffold(
        appBar: AppBar(title: const Text('Quiz')),
        body: const Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              CircularProgressIndicator(color: AppColors.primary),
              SizedBox(height: 16),
              Text(
                'Loading quiz...',
                style: TextStyle(color: AppColors.gray600, fontSize: 15),
              ),
            ],
          ),
        ),
      );
    }

    // ── ERROR ─────────────────────────────────────────────────
    if (quiz.status == QuizStatus.error) {
      return Scaffold(
        appBar: AppBar(title: const Text('Quiz')),
        body: Center(
          child: Padding(
            padding: const EdgeInsets.symmetric(horizontal: 32),
            child: Column(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    color: AppColors.danger.withOpacity(0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Row(
                    children: [
                      const Icon(Icons.error_outline,
                          color: AppColors.danger, size: 20),
                      const SizedBox(width: 10),
                      Expanded(
                        child: Text(
                          quiz.errorMessage ?? 'Failed to load quiz.',
                          style: const TextStyle(
                              color: AppColors.danger, fontSize: 14),
                        ),
                      ),
                    ],
                  ),
                ),
                const SizedBox(height: 20),
                ElevatedButton(
                  onPressed: () => context.go('/categories'),
                  child: const Text('Go Back'),
                ),
              ],
            ),
          ),
        ),
      );
    }

    // ── EMPTY ─────────────────────────────────────────────────
    if (quiz.questions.isEmpty) {
      return Scaffold(
        appBar: AppBar(title: const Text('Quiz')),
        body: Center(
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Text('😕', style: TextStyle(fontSize: 64)),
              const SizedBox(height: 16),
              const Text(
                'No questions available\nfor this category.',
                textAlign: TextAlign.center,
                style: TextStyle(fontSize: 16, color: AppColors.gray600),
              ),
              const SizedBox(height: 24),
              ElevatedButton(
                onPressed: () => context.go('/categories'),
                child: const Text('Go Back'),
              ),
            ],
          ),
        ),
      );
    }

    // ── ACTIVE QUIZ ───────────────────────────────────────────
    final question = quiz.currentQuestion!;
    final isShortAnswer = question.questionType == 'short_answer';
    final isMultiSelect = question.questionType == 'multi_select';
    final isTrueFalse = question.questionType == 'true_false';

    final selectedOptionId = quiz.answers[quiz.currentIndex];
    final displayedOptions = isTrueFalse
        ? question.options.take(2).toList()
        : question.options;
    final answeredIndex = selectedOptionId == null
        ? -1
        : displayedOptions.indexWhere((o) => o.id == selectedOptionId);

    // For multi-select questions, get all selected option IDs
    final selectedOptionIds = quiz.multiAnswers[quiz.currentIndex] ?? <int>{};
    final selectedIndices = <int>{};
    for (int i = 0; i < question.options.length; i++) {
      if (selectedOptionIds.contains(question.options[i].id)) {
        selectedIndices.add(i);
      }
    }

    final hasTextAnswer = (quiz.textAnswers[quiz.currentIndex] ?? '').isNotEmpty;
    final hasMultiAnswer = selectedOptionIds.isNotEmpty;
    final isAnswered = isShortAnswer
        ? hasTextAnswer
        : isMultiSelect
            ? hasMultiAnswer
            : selectedOptionId != null;
    final progress = (quiz.currentIndex + 1) / quiz.totalQuestions;
    final isFirst  = quiz.currentIndex == 0;

    if (_lastQuestionId != question.id) {
      _lastQuestionId = question.id;
      if (isShortAnswer) {
        _textController.text =
            quiz.textAnswers[quiz.currentIndex] ?? '';
      }
    }

    return Scaffold(
      backgroundColor: AppColors.gray100,
      appBar: AppBar(
        title: Text(
            'Question ${quiz.currentIndex + 1} of ${quiz.totalQuestions}'),
        actions: [
          Container(
            margin: const EdgeInsets.only(right: 12, top: 8, bottom: 8),
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
            decoration: BoxDecoration(
              color: quiz.isExpired ? AppColors.danger : AppColors.primary,
              borderRadius: BorderRadius.circular(16),
            ),
            child: Center(
              child: Text(
                quiz.timerLabel,
                style: const TextStyle(
                  color: Colors.white,
                  fontWeight: FontWeight.w800,
                ),
              ),
            ),
          ),
        ],
        leading: IconButton(
          icon: const Icon(Icons.close),
          onPressed: () {
            showDialog(
              context: context,
              builder: (_) => AlertDialog(
                title: const Text('Quit Quiz?'),
                content: const Text('Your progress will be lost.'),
                actions: [
                  TextButton(
                    onPressed: () => Navigator.pop(context),
                    child: const Text('Cancel'),
                  ),
                  TextButton(
                    onPressed: () {
                      context.read<QuizProvider>().reset();
                      context.go('/categories');
                    },
                    child: const Text(
                      'Quit',
                      style: TextStyle(color: AppColors.danger),
                    ),
                  ),
                ],
              ),
            );
          },
        ),
      ),
      body: Column(
        children: [
          // Progress bar
          LinearProgressIndicator(
            value: progress,
            backgroundColor: AppColors.gray200,
            color: AppColors.primary,
            minHeight: 6,
          ),
          if (quiz.saveStatusLabel.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(20, 10, 20, 0),
              child: Align(
                alignment: Alignment.centerRight,
                child: Text(
                  quiz.saveStatusLabel,
                  style: TextStyle(
                    color: quiz.saveStatus == SaveStatus.retrying
                        ? AppColors.danger
                        : AppColors.gray600,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),

          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  const SizedBox(height: 8),

                  if (quiz.isExpired) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.danger.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                            color: AppColors.danger.withOpacity(0.3)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.timer_off,
                              color: AppColors.danger, size: 18),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Time is up. You can no longer answer.',
                              style: TextStyle(
                                  color: AppColors.danger, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                  if (quiz.isSubmitted) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                            color: AppColors.primary.withOpacity(0.3)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.lock_outline,
                              color: AppColors.primary, size: 18),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'This attempt is submitted and read-only.',
                              style: TextStyle(
                                  color: AppColors.primary, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  // Unanswered warning banner
                  if (_showUnansweredWarning) ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.danger.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                            color: AppColors.danger.withOpacity(0.3)),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.warning_amber_rounded,
                              color: AppColors.danger, size: 18),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Please answer this question before finishing.',
                              style: TextStyle(
                                  color: AppColors.danger, fontSize: 13),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],

                  // Question card
                  Container(
                    padding: const EdgeInsets.all(24),
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(16),
                      boxShadow: [
                        BoxShadow(
                          color: Colors.black.withOpacity(0.06),
                          blurRadius: 12,
                          offset: const Offset(0, 4),
                        ),
                      ],
                    ),
                    child: Text(
                      question.questionText,
                      style: const TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textDark,
                        height: 1.4,
                      ),
                    ),
                  ),
                  const SizedBox(height: 20),

                  if (isShortAnswer) ...[
                    TextField(
                      controller: _textController,
                      enabled: !quiz.isLocked,
                      decoration: const InputDecoration(
                        hintText: 'Type your answer...',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 3,
                      onChanged: (value) {
                        context.read<QuizProvider>().updateTextDraft(value);
                        setState(() => _showUnansweredWarning = false);
                      },
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: quiz.isLocked
                            ? null
                            : () {
                                context
                                    .read<QuizProvider>()
                                    .answerText(_textController.text);
                                setState(() => _showUnansweredWarning = false);
                              },
                        child: const Text('Save Answer'),
                      ),
                    ),
                  ] else if (isMultiSelect) ...[
                    // Multi-Select Options (Checkboxes)
                    ...List.generate(question.options.length, (i) {
                      final isSelected = selectedIndices.contains(i);
                      return GestureDetector(
                        onTap: quiz.isLocked
                            ? null
                            : () {
                                context.read<QuizProvider>().answerQuestionMulti(i);
                                setState(() => _showUnansweredWarning = false);
                              },
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 150),
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primary
                                : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primary
                                  : AppColors.gray200,
                              width: isSelected ? 2 : 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  borderRadius: BorderRadius.circular(6),
                                  color: isSelected
                                      ? Colors.white.withOpacity(0.2)
                                      : AppColors.gray100,
                                  border: Border.all(
                                    color: isSelected
                                        ? Colors.white
                                        : AppColors.gray400,
                                  ),
                                ),
                                child: Center(
                                  child: isSelected
                                      ? const Icon(Icons.check,
                                          color: Colors.white, size: 18)
                                      : null,
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Text(
                                  question.options[i].optionText,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w600,
                                    color: isSelected
                                        ? Colors.white
                                        : AppColors.textDark,
                                  ),
                                ),
                              ),
                            ],
                          ),
                        ),
                      );
                    }),
                  ] else if (isTrueFalse) ...[
                    // True/False Options (Radio style, 2 options only)
                    ...List.generate(displayedOptions.length, (i) {
                      final option = displayedOptions[i];
                      final isSelected = answeredIndex == i;
                      final optionLabel = option.optionText.isNotEmpty
                          ? option.optionText
                          : (i == 0 ? 'True' : 'False');
                      return GestureDetector(
                        onTap: quiz.isLocked
                            ? null
                            : () {
                                context.read<QuizProvider>().answerQuestion(
                                  question.options.indexOf(option),
                                );
                                setState(() => _showUnansweredWarning = false);
                              },
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 150),
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primary
                                : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primary
                                  : AppColors.gray200,
                              width: isSelected ? 2 : 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: isSelected
                                      ? Colors.white.withOpacity(0.2)
                                      : AppColors.gray100,
                                ),
                                child: Center(
                                  child: Text(
                                    optionLabel[0],
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 13,
                                      color: isSelected
                                          ? Colors.white
                                          : AppColors.gray600,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Text(
                                  optionLabel,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w600,
                                    color: isSelected
                                        ? Colors.white
                                        : AppColors.textDark,
                                  ),
                                ),
                              ),
                              if (isSelected)
                                const Icon(Icons.check_circle,
                                    color: Colors.white, size: 20),
                            ],
                          ),
                        ),
                      );
                    }),
                  ] else ...[
                    // MCQ Options (Radio style, single selection)
                    ...List.generate(question.options.length, (i) {
                      final isSelected = answeredIndex == i;
                      return GestureDetector(
                        onTap: quiz.isLocked
                            ? null
                            : () {
                                context.read<QuizProvider>().answerQuestion(i);
                                setState(() => _showUnansweredWarning = false);
                              },
                        child: AnimatedContainer(
                          duration: const Duration(milliseconds: 150),
                          margin: const EdgeInsets.only(bottom: 12),
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: isSelected
                                ? AppColors.primary
                                : Colors.white,
                            borderRadius: BorderRadius.circular(12),
                            border: Border.all(
                              color: isSelected
                                  ? AppColors.primary
                                  : AppColors.gray200,
                              width: isSelected ? 2 : 1,
                            ),
                            boxShadow: [
                              BoxShadow(
                                color: Colors.black.withOpacity(0.04),
                                blurRadius: 6,
                                offset: const Offset(0, 2),
                              ),
                            ],
                          ),
                          child: Row(
                            children: [
                              Container(
                                width: 32,
                                height: 32,
                                decoration: BoxDecoration(
                                  shape: BoxShape.circle,
                                  color: isSelected
                                      ? Colors.white.withOpacity(0.2)
                                      : AppColors.gray100,
                                ),
                                child: Center(
                                  child: Text(
                                    ['A', 'B', 'C', 'D'][i],
                                    style: TextStyle(
                                      fontWeight: FontWeight.w800,
                                      fontSize: 13,
                                      color: isSelected
                                          ? Colors.white
                                          : AppColors.gray600,
                                    ),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Text(
                                  question.options[i].optionText,
                                  style: TextStyle(
                                    fontSize: 15,
                                    fontWeight: FontWeight.w600,
                                    color: isSelected
                                        ? Colors.white
                                        : AppColors.textDark,
                                  ),
                                ),
                              ),
                              if (isSelected)
                                const Icon(Icons.check_circle,
                                    color: Colors.white, size: 20),
                            ],
                          ),
                        ),
                      );
                    }),
                  ],
                ],
              ),
            ),
          ),

          // Bottom navigation
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
            child: Row(
              children: [
                // Previous — hidden on first question
                if (!isFirst) ...[
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        context.read<QuizProvider>().previousQuestion();
                        setState(() => _showUnansweredWarning = false);
                      },
                      icon: const Icon(Icons.arrow_back_ios, size: 14),
                      label: const Text('Previous'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppColors.primary,
                        side: const BorderSide(color: AppColors.primary),
                        minimumSize: const Size(0, 52),
                        shape: RoundedRectangleBorder(
                          borderRadius: BorderRadius.circular(14),
                        ),
                      ),
                    ),
                  ),
                  const SizedBox(width: 12),
                ],

                // Next / Finish button
                Expanded(
                  child: ElevatedButton(
                    onPressed: quiz.isLocked || _isSubmitting
                        ? null
                        : () async {
                            if (!isAnswered) {
                              setState(() => _showUnansweredWarning = true);
                              return;
                            }
                            if (quiz.isLastQuestion) {
                              setState(() => _isSubmitting = true);
                              try {
                                await context.read<QuizProvider>().submitAttempt();
                                if (context.read<QuizProvider>().status ==
                                    QuizStatus.finished) {
                                  context.go('/quiz-result');
                                }
                              } finally {
                                setState(() => _isSubmitting = false);
                              }
                            } else {
                              context.read<QuizProvider>().nextQuestion();
                            }
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: !isAnswered
                          ? AppColors.gray200
                          : AppColors.accent,
                      minimumSize: const Size(double.infinity, 52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: _isSubmitting
                        ? const SizedBox(
                            height: 18,
                            width: 18,
                            child: CircularProgressIndicator(
                              strokeWidth: 2,
                              color: Colors.white,
                            ),
                          )
                        : Text(
                            quiz.isLastQuestion ? 'Finish Quiz' : 'Next Question',
                            style: TextStyle(
                              color: !isAnswered
                                  ? AppColors.gray600
                                  : Colors.white,
                              fontWeight: FontWeight.w700,
                              fontSize: 16,
                            ),
                          ),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
