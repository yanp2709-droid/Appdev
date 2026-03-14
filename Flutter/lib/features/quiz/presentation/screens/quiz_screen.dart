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

  @override
  void dispose() {
    _textController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final quiz = context.watch<QuizProvider>();

    // ── LOADING ──────────────────────────────
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

    // ── ERROR ────────────────────────────────
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

    // ── EMPTY ─────────────────────────────────
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

    // ── ACTIVE QUIZ ──────────────────────────
    final question = quiz.currentQuestion!;
    final selectedOptionId = quiz.answers[quiz.currentIndex];
    final answeredIndex = selectedOptionId == null
        ? -1
        : question.options.indexWhere((o) => o.id == selectedOptionId);
    final hasTextAnswer = (quiz.textAnswers[quiz.currentIndex] ?? '').isNotEmpty;
    final progress = (quiz.currentIndex + 1) / quiz.totalQuestions;
    final isFirst = quiz.currentIndex == 0;

    if (_lastQuestionId != question.id) {
      _lastQuestionId = question.id;
      if (question.questionType == 'short_answer') {
        _textController.text = quiz.textAnswers[quiz.currentIndex] ?? '';
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
          Expanded(
            child: SingleChildScrollView(
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.stretch,
                children: [
                  // ... [existing question card, MCQ and text field UI unchanged] ...
                ],
              ),
            ),
          ),

          // ── FIXED BOTTOM NAVIGATION ─────────────────────────
          Padding(
            padding: const EdgeInsets.fromLTRB(20, 0, 20, 32),
            child: Row(
              children: [
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
                Expanded(
                  child: ElevatedButton(
                    onPressed: () {
                      if (quiz.isExpired) return;

                      final unanswered = question.questionType == 'short_answer'
                          ? !hasTextAnswer
                          : answeredIndex == -1;

                      if (unanswered) {
                        setState(() => _showUnansweredWarning = true);
                        return;
                      }

                      if (quiz.isLastQuestion) {
                        context.read<QuizProvider>().submitAttempt().then((_) {
                          if (context.read<QuizProvider>().status ==
                              QuizStatus.finished) {
                            context.go('/quiz-result');
                          }
                        });
                      } else {
                        context.read<QuizProvider>().nextQuestion();
                      }
                    },
                    style: ElevatedButton.styleFrom(
                      backgroundColor: (question.questionType == 'short_answer'
                              ? !hasTextAnswer
                              : answeredIndex == -1)
                          ? AppColors.gray200
                          : AppColors.accent,
                      minimumSize: const Size(double.infinity, 52),
                      shape: RoundedRectangleBorder(
                        borderRadius: BorderRadius.circular(14),
                      ),
                    ),
                    child: Text(
                      quiz.isLastQuestion ? 'Finish Quiz' : 'Next Question',
                      style: TextStyle(
                        color: (question.questionType == 'short_answer'
                                ? !hasTextAnswer
                                : answeredIndex == -1)
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