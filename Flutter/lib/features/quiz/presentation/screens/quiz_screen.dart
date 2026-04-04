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

class _QuizScreenState extends State<QuizScreen> {
  bool _showUnansweredWarning = false;
  final TextEditingController _textController = TextEditingController();
  int? _lastQuestionId;
  bool _isSubmitting = false;
  bool _isReviewOpen = false;

  @override
  void dispose() {
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
    final selectedOptionIds = quiz.selectedOptionIdsFor(quiz.currentIndex);
    final selectedOptionId =
        selectedOptionIds.length == 1 ? selectedOptionIds.first : null;
    final answeredIndex = selectedOptionId == null
        ? -1
        : question.options.indexWhere((o) => o.id == selectedOptionId);
    final progress = (quiz.currentIndex + 1) / quiz.totalQuestions;
    final isFirst  = quiz.currentIndex == 0;
    final isBookmarked = quiz.isBookmarked(quiz.currentIndex);

    if (_lastQuestionId != question.id) {
      _lastQuestionId = question.id;
      if (question.questionType == 'short_answer') {
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
          IconButton(
            tooltip: isBookmarked ? 'Remove bookmark' : 'Bookmark question',
            onPressed: quiz.isExpired
                ? null
                : () => context.read<QuizProvider>().toggleBookmark(),
            icon: Icon(
              isBookmarked ? Icons.bookmark : Icons.bookmark_border,
              color: isBookmarked ? AppColors.accent : Colors.white,
            ),
          ),
          IconButton(
            tooltip: 'Question palette',
            onPressed: () => _showQuestionPalette(context, quiz),
            icon: const Icon(Icons.grid_view_rounded, color: Colors.white),
          ),
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

                  if (question.questionType == 'short_answer') ...[
                    TextField(
                      controller: _textController,
                      enabled: !quiz.isExpired,
                      decoration: const InputDecoration(
                        hintText: 'Type your answer...',
                        border: OutlineInputBorder(),
                      ),
                      maxLines: 3,
                      onChanged: (_) => setState(() => _showUnansweredWarning = false),
                    ),
                    const SizedBox(height: 12),
                    SizedBox(
                      width: double.infinity,
                      child: ElevatedButton(
                        onPressed: quiz.isExpired
                            ? null
                            : () async {
                                await context
                                    .read<QuizProvider>()
                                    .answerText(_textController.text);
                                setState(() => _showUnansweredWarning = false);
                              },
                        child: const Text('Save Answer'),
                      ),
                    ),
                  ] else if (question.questionType == 'multi_select') ...[
                    Container(
                      margin: const EdgeInsets.only(bottom: 12),
                      padding: const EdgeInsets.symmetric(
                          horizontal: 14, vertical: 10),
                      decoration: BoxDecoration(
                        color: AppColors.primary.withOpacity(0.08),
                        borderRadius: BorderRadius.circular(10),
                        border: Border.all(
                          color: AppColors.primary.withOpacity(0.2),
                        ),
                      ),
                      child: const Row(
                        children: [
                          Icon(Icons.checklist_rounded,
                              color: AppColors.primary, size: 18),
                          SizedBox(width: 10),
                          Expanded(
                            child: Text(
                              'Select all answers that apply.',
                              style: TextStyle(
                                color: AppColors.primary,
                                fontSize: 13,
                                fontWeight: FontWeight.w600,
                              ),
                            ),
                          ),
                        ],
                      ),
                    ),
                    ...List.generate(question.options.length, (i) {
                      final option = question.options[i];
                      final isSelected =
                          quiz.isOptionSelected(quiz.currentIndex, option.id);
                      return GestureDetector(
                        onTap: quiz.isExpired
                            ? null
                            : () {
                                context
                                    .read<QuizProvider>()
                                    .toggleMultiSelectOption(i);
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
                              Icon(
                                isSelected
                                    ? Icons.check_box_rounded
                                    : Icons.check_box_outline_blank_rounded,
                                color: isSelected
                                    ? Colors.white
                                    : AppColors.gray600,
                              ),
                              const SizedBox(width: 14),
                              Expanded(
                                child: Text(
                                  option.optionText,
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
                  ] else ...[
                    // Options
                    ...List.generate(question.options.length, (i) {
                      final isSelected = answeredIndex == i;
                      return GestureDetector(
                        onTap: quiz.isExpired
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
                                    String.fromCharCode(65 + i),
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
                    onPressed: quiz.isExpired || _isSubmitting
                        ? null
                        : () async {
                            if (quiz.isLastQuestion) {
                              await _handleFinishPressed(context, quiz);
                            } else {
                              context.read<QuizProvider>().nextQuestion();
                            }
                          },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: AppColors.accent,
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
                              color: Colors.white,
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

  Future<void> _handleFinishPressed(BuildContext context, QuizProvider quiz) async {
    if (_isReviewOpen) return;
    if (quiz.allowReviewBeforeSubmit) {
      _isReviewOpen = true;
      final shouldSubmit = await _showReviewSheet(context, quiz);
      _isReviewOpen = false;
      if (shouldSubmit == true) {
        await _submitAttempt(context, quiz);
      }
      return;
    }

    if (quiz.unansweredCount > 0) {
      final confirmed = await _confirmUnansweredSubmit(context, quiz.unansweredCount);
      if (!confirmed) return;
    }

    await _submitAttempt(context, quiz);
  }

  Future<void> _submitAttempt(BuildContext context, QuizProvider quiz) async {
    setState(() => _isSubmitting = true);
    try {
      await context.read<QuizProvider>().submitAttempt();
      if (context.read<QuizProvider>().status == QuizStatus.finished) {
        context.go('/quiz-result');
      }
    } finally {
      if (mounted) {
        setState(() => _isSubmitting = false);
      }
    }
  }

  Future<void> _showQuestionPalette(BuildContext context, QuizProvider quiz) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (_) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Question Palette',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textDark,
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Flexible(
                  child: GridView.builder(
                    shrinkWrap: true,
                    itemCount: quiz.totalQuestions,
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 5,
                      mainAxisSpacing: 10,
                      crossAxisSpacing: 10,
                    ),
                    itemBuilder: (context, index) {
                      final isCurrent = index == quiz.currentIndex;
                      final isAnswered = quiz.isQuestionAnswered(index);
                      final isBookmarked = quiz.isBookmarked(index);
                      final colors = _paletteColors(
                        isCurrent: isCurrent,
                        isAnswered: isAnswered,
                        isBookmarked: isBookmarked,
                      );
                      return GestureDetector(
                        onTap: () {
                          quiz.jumpToQuestion(index);
                          setState(() => _showUnansweredWarning = false);
                          Navigator.pop(context);
                        },
                        child: Container(
                          decoration: BoxDecoration(
                            color: colors.background,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: colors.border, width: 2),
                          ),
                          child: Center(
                            child: Text(
                              '${index + 1}',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                color: colors.text,
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(height: 12),
                Wrap(
                  spacing: 12,
                  runSpacing: 6,
                  children: const [
                    _PaletteLegend(label: 'Current', color: AppColors.primary),
                    _PaletteLegend(label: 'Answered', color: Color(0xFF16A34A)),
                    _PaletteLegend(label: 'Bookmarked', color: Color(0xFFF59E0B)),
                    _PaletteLegend(label: 'Unanswered', color: AppColors.gray200),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<bool?> _showReviewSheet(BuildContext context, QuizProvider quiz) async {
    return showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      backgroundColor: Colors.white,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (_) {
        return SafeArea(
          child: Padding(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 24),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                Row(
                  mainAxisAlignment: MainAxisAlignment.spaceBetween,
                  children: [
                    const Text(
                      'Review Before Submit',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w700,
                        color: AppColors.textDark,
                      ),
                    ),
                    IconButton(
                      onPressed: () => Navigator.pop(context, false),
                      icon: const Icon(Icons.close),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                _SummaryRow(
                  total: quiz.totalQuestions,
                  answered: quiz.answeredCount,
                  unanswered: quiz.unansweredCount,
                  bookmarked: quiz.bookmarkedCount,
                ),
                const SizedBox(height: 16),
                Flexible(
                  child: GridView.builder(
                    shrinkWrap: true,
                    itemCount: quiz.totalQuestions,
                    gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                      crossAxisCount: 5,
                      mainAxisSpacing: 10,
                      crossAxisSpacing: 10,
                    ),
                    itemBuilder: (context, index) {
                      final isCurrent = index == quiz.currentIndex;
                      final isAnswered = quiz.isQuestionAnswered(index);
                      final isBookmarked = quiz.isBookmarked(index);
                      final colors = _paletteColors(
                        isCurrent: isCurrent,
                        isAnswered: isAnswered,
                        isBookmarked: isBookmarked,
                      );
                      return GestureDetector(
                        onTap: () {
                          quiz.jumpToQuestion(index);
                          Navigator.pop(context, false);
                        },
                        child: Container(
                          decoration: BoxDecoration(
                            color: colors.background,
                            borderRadius: BorderRadius.circular(10),
                            border: Border.all(color: colors.border, width: 2),
                          ),
                          child: Center(
                            child: Text(
                              '${index + 1}',
                              style: TextStyle(
                                fontWeight: FontWeight.w700,
                                color: colors.text,
                              ),
                            ),
                          ),
                        ),
                      );
                    },
                  ),
                ),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: () async {
                    if (quiz.unansweredCount > 0) {
                      final confirmed = await _confirmUnansweredSubmit(
                        context,
                        quiz.unansweredCount,
                      );
                      if (!confirmed) return;
                    }
                    if (context.mounted) {
                      Navigator.pop(context, true);
                    }
                  },
                  style: ElevatedButton.styleFrom(
                    backgroundColor: AppColors.accent,
                    minimumSize: const Size(double.infinity, 52),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                  ),
                  child: const Text(
                    'Submit Quiz',
                    style: TextStyle(
                      color: Colors.white,
                      fontWeight: FontWeight.w700,
                      fontSize: 16,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                OutlinedButton(
                  onPressed: () => Navigator.pop(context, false),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: AppColors.primary,
                    minimumSize: const Size(double.infinity, 52),
                    shape: RoundedRectangleBorder(
                      borderRadius: BorderRadius.circular(14),
                    ),
                    side: const BorderSide(color: AppColors.primary),
                  ),
                  child: const Text('Back to Quiz'),
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  Future<bool> _confirmUnansweredSubmit(BuildContext context, int unanswered) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Submit With Unanswered?'),
        content: Text('You have $unanswered unanswered question(s). Submit anyway?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context, false),
            child: const Text('Go Back'),
          ),
          TextButton(
            onPressed: () => Navigator.pop(context, true),
            child: const Text(
              'Submit',
              style: TextStyle(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );
    return result ?? false;
  }

  _PaletteVisuals _paletteColors({
    required bool isCurrent,
    required bool isAnswered,
    required bool isBookmarked,
  }) {
    if (isCurrent) {
      return const _PaletteVisuals(
        background: AppColors.primary,
        border: AppColors.primaryDark,
        text: Colors.white,
      );
    }
    if (isBookmarked) {
      return const _PaletteVisuals(
        background: Color(0xFFFFF7ED),
        border: Color(0xFFF59E0B),
        text: AppColors.textDark,
      );
    }
    if (isAnswered) {
      return const _PaletteVisuals(
        background: Color(0xFFDCFCE7),
        border: Color(0xFF16A34A),
        text: AppColors.textDark,
      );
    }
    return const _PaletteVisuals(
      background: AppColors.gray200,
      border: AppColors.gray400,
      text: AppColors.textDark,
    );
  }
}

class _PaletteVisuals {
  final Color background;
  final Color border;
  final Color text;

  const _PaletteVisuals({
    required this.background,
    required this.border,
    required this.text,
  });
}

class _PaletteLegend extends StatelessWidget {
  final String label;
  final Color color;

  const _PaletteLegend({
    required this.label,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        Container(
          width: 10,
          height: 10,
          decoration: BoxDecoration(
            color: color,
            shape: BoxShape.circle,
          ),
        ),
        const SizedBox(width: 6),
        Text(
          label,
          style: const TextStyle(fontSize: 12, color: AppColors.gray600),
        ),
      ],
    );
  }
}

class _SummaryRow extends StatelessWidget {
  final int total;
  final int answered;
  final int unanswered;
  final int bookmarked;

  const _SummaryRow({
    required this.total,
    required this.answered,
    required this.unanswered,
    required this.bookmarked,
  });

  @override
  Widget build(BuildContext context) {
    return Wrap(
      spacing: 12,
      runSpacing: 8,
      children: [
        _SummaryChip(label: 'Total', value: '$total', color: AppColors.gray200),
        _SummaryChip(label: 'Answered', value: '$answered', color: const Color(0xFFDCFCE7)),
        _SummaryChip(label: 'Unanswered', value: '$unanswered', color: AppColors.dangerBg),
        _SummaryChip(label: 'Bookmarked', value: '$bookmarked', color: const Color(0xFFFFF7ED)),
      ],
    );
  }
}

class _SummaryChip extends StatelessWidget {
  final String label;
  final String value;
  final Color color;

  const _SummaryChip({
    required this.label,
    required this.value,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: color,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        '$label: $value',
        style: const TextStyle(
          fontSize: 12,
          fontWeight: FontWeight.w600,
          color: AppColors.textDark,
        ),
      ),
    );
  }
}
