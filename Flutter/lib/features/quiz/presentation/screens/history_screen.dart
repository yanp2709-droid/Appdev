import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../../core/constants/app_colors.dart';
import '../../../../services/attempt_history_service.dart';
import '../../data/models/attempt_detail.dart';
import '../../data/models/attempt_history.dart';

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key});

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  final AttemptHistoryService _historyService = AttemptHistoryService();
  late Future<List<AttemptHistoryModel>> _historiesDataFuture;
  final Map<int, Future<AttemptDetailModel>> _detailFutures = {};
  int? _selectedQuizId;

  @override
  void initState() {
    super.initState();
    _historiesDataFuture = _loadAllHistory();
  }

  @override
  Widget build(BuildContext context) {
    return PopScope(
      canPop: context.canPop(),
      onPopInvokedWithResult: (didPop, result) {
        if (!didPop && mounted) {
          context.go('/student-home');
        }
      },
      child: Scaffold(
        appBar: AppBar(
          title: const Text('Quiz History'),
          centerTitle: true,
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          automaticallyImplyLeading: true,
          leading: IconButton(
            icon: const Icon(Icons.arrow_back),
            onPressed: () {
              if (context.canPop()) {
                context.pop();
              } else {
                context.go('/student-home');
              }
            },
          ),
        ),
        body: FutureBuilder<List<AttemptHistoryModel>>(
          future: _historiesDataFuture,
          builder: (context, snapshot) {
            if (snapshot.connectionState == ConnectionState.waiting) {
              return const Center(child: CircularProgressIndicator());
            }

            if (snapshot.hasError) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.error_outline, size: 64, color: Colors.red),
                    const SizedBox(height: 16),
                    Text(
                      'Failed to load history',
                      style: Theme.of(context).textTheme.bodyLarge,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      snapshot.error.toString(),
                      style: Theme.of(context)
                          .textTheme
                          .bodyMedium
                          ?.copyWith(color: Colors.grey),
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 20),
                    ElevatedButton(
                      onPressed: () => setState(() {
                        _historiesDataFuture = _loadAllHistory();
                      }),
                      child: const Text('Retry'),
                    ),
                  ],
                ),
              );
            }

            if (!snapshot.hasData || snapshot.data!.isEmpty) {
              return Center(
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    const Icon(Icons.history, size: 64, color: AppColors.primary),
                    const SizedBox(height: 16),
                    Text(
                      'No attempts yet',
                      style: Theme.of(context).textTheme.bodyLarge?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'Start taking quizzes to see your quiz history',
                      style: Theme.of(context)
                          .textTheme
                          .bodyMedium
                          ?.copyWith(color: Colors.grey),
                    ),
                  ],
                ),
              );
            }

            final attempts = snapshot.data!;
            _primeDetailFutures(attempts);

            final grouped = _groupByQuiz(attempts);
            final quizSummaries = _buildQuizSummaries(grouped);
            final validQuizIds = quizSummaries.map((quiz) => quiz.quizId).toSet();

            if (_selectedQuizId != null && !validQuizIds.contains(_selectedQuizId)) {
              WidgetsBinding.instance.addPostFrameCallback((_) {
                if (mounted) {
                  setState(() {
                    _selectedQuizId = null;
                  });
                }
              });
            }

            final visibleQuizzes = _selectedQuizId == null
                ? quizSummaries
                : quizSummaries.where((quiz) => quiz.quizId == _selectedQuizId).toList();

            return Column(
              children: [
                _QuizFilterBar(
                  quizzes: quizSummaries,
                  selectedQuizId: _selectedQuizId,
                  onSelected: (quizId) {
                    setState(() {
                      _selectedQuizId = quizId;
                    });
                  },
                ),
                Expanded(
                  child: ListView.builder(
                    padding: const EdgeInsets.fromLTRB(12, 4, 12, 12),
                    itemCount: visibleQuizzes.length,
                    itemBuilder: (context, index) {
                      final quiz = visibleQuizzes[index];
                      return _QuizSection(
                        quizTitle: quiz.quizTitle,
                        categoryName: quiz.categoryName,
                        attempts: grouped[quiz.quizId] ?? const [],
                        onAttemptTap: (id) => _navigateToDetail(context, id),
                        detailFutureFor: (id) => _detailFutures[id],
                      );
                    },
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  void _navigateToDetail(BuildContext context, int attemptId) {
    context.push('/history/$attemptId');
  }

  Future<List<AttemptHistoryModel>> _loadAllHistory() async {
    const perPage = 50;
    const maxPages = 20;
    final results = <AttemptHistoryModel>[];

    for (var page = 1; page <= maxPages; page++) {
      final pageItems = await _historyService.getHistory(
        page: page,
        perPage: perPage,
      );
      if (pageItems.isEmpty) {
        break;
      }

      results.addAll(pageItems);

      if (pageItems.length < perPage) {
        break;
      }
    }

    return results;
  }

  void _primeDetailFutures(List<AttemptHistoryModel> attempts) {
    var added = false;
    for (final attempt in attempts) {
      if (!_detailFutures.containsKey(attempt.id)) {
        _detailFutures[attempt.id] =
            _historyService.getAttemptDetail(attemptId: attempt.id);
        added = true;
      }
    }
    if (added) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          setState(() {});
        }
      });
    }
  }

  Map<int, List<AttemptHistoryModel>> _groupByQuiz(List<AttemptHistoryModel> attempts) {
    final grouped = <int, List<AttemptHistoryModel>>{};
    for (final attempt in attempts) {
      grouped.putIfAbsent(attempt.quizId, () => []).add(attempt);
    }
    return grouped;
  }

  List<_QuizSummary> _buildQuizSummaries(Map<int, List<AttemptHistoryModel>> grouped) {
    final summaries = grouped.entries.map((entry) {
      final attempts = entry.value;
      attempts.sort((a, b) {
        final aDate = a.submittedAt ?? a.startedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        final bDate = b.submittedAt ?? b.startedAt ?? DateTime.fromMillisecondsSinceEpoch(0);
        return bDate.compareTo(aDate);
      });

      final first = attempts.first;
      return _QuizSummary(
        quizId: entry.key,
        quizTitle: first.quizTitle,
        categoryName: first.categoryName,
        latestAttemptAt: first.submittedAt ?? first.startedAt,
      );
    }).toList();

    summaries.sort((a, b) {
      final aDate = a.latestAttemptAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      final bDate = b.latestAttemptAt ?? DateTime.fromMillisecondsSinceEpoch(0);
      return bDate.compareTo(aDate);
    });

    return summaries;
  }
}

class _QuizFilterBar extends StatelessWidget {
  final List<_QuizSummary> quizzes;
  final int? selectedQuizId;
  final ValueChanged<int?> onSelected;

  const _QuizFilterBar({
    required this.quizzes,
    required this.selectedQuizId,
    required this.onSelected,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.fromLTRB(12, 12, 12, 8),
      color: Colors.white,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Text(
            'View history by quiz',
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: AppColors.primary,
            ),
          ),
          const SizedBox(height: 8),
          SingleChildScrollView(
            scrollDirection: Axis.horizontal,
            child: Row(
              children: [
                Padding(
                  padding: const EdgeInsets.only(right: 8),
                  child: ChoiceChip(
                    label: const Text('All quizzes'),
                    selected: selectedQuizId == null,
                    onSelected: (_) => onSelected(null),
                  ),
                ),
                ...quizzes.map((quiz) {
                  return Padding(
                    padding: const EdgeInsets.only(right: 8),
                    child: ChoiceChip(
                      label: Text(quiz.quizTitle),
                      selected: selectedQuizId == quiz.quizId,
                      onSelected: (_) => onSelected(quiz.quizId),
                    ),
                  );
                }),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _QuizSection extends StatelessWidget {
  final String quizTitle;
  final String categoryName;
  final List<AttemptHistoryModel> attempts;
  final ValueChanged<int> onAttemptTap;
  final Future<AttemptDetailModel>? Function(int attemptId) detailFutureFor;

  const _QuizSection({
    required this.quizTitle,
    required this.categoryName,
    required this.attempts,
    required this.onAttemptTap,
    required this.detailFutureFor,
  });

  @override
  Widget build(BuildContext context) {
    final officialAttempts =
        attempts.where((attempt) => attempt.isOfficialGradedAttempt).toList();
    final practiceAttempts =
        attempts.where((attempt) => attempt.isPracticeAttempt).toList();

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              quizTitle,
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w800,
                color: AppColors.primary,
              ),
            ),
            const SizedBox(height: 2),
            Text(
              categoryName,
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.gray600,
              ),
            ),
            const SizedBox(height: 4),
            Text(
              '${attempts.length} attempt${attempts.length == 1 ? '' : 's'}',
              style: const TextStyle(
                fontSize: 12,
                fontWeight: FontWeight.w600,
                color: AppColors.gray600,
              ),
            ),
            const SizedBox(height: 10),
            if (officialAttempts.isNotEmpty) ...[
              const Text(
                'Official Graded Attempt',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: AppColors.gray600,
                ),
              ),
              const SizedBox(height: 8),
              ...officialAttempts.map((attempt) {
                return _AttemptDetailCard(
                  attempt: attempt,
                  onTap: () => onAttemptTap(attempt.id),
                  detailFuture: detailFutureFor(attempt.id),
                );
              }),
            ],
            if (practiceAttempts.isNotEmpty) ...[
              if (officialAttempts.isNotEmpty) const SizedBox(height: 8),
              const Text(
                'Practice Attempts',
                style: TextStyle(
                  fontSize: 12,
                  fontWeight: FontWeight.w800,
                  color: AppColors.gray600,
                ),
              ),
              const SizedBox(height: 8),
              ...practiceAttempts.map((attempt) {
                return _AttemptDetailCard(
                  attempt: attempt,
                  onTap: () => onAttemptTap(attempt.id),
                  detailFuture: detailFutureFor(attempt.id),
                );
              }),
            ],
          ],
        ),
      ),
    );
  }
}

class _AttemptDetailCard extends StatelessWidget {
  final AttemptHistoryModel attempt;
  final VoidCallback onTap;
  final Future<AttemptDetailModel>? detailFuture;

  const _AttemptDetailCard({
    required this.attempt,
    required this.onTap,
    required this.detailFuture,
  });

  @override
  Widget build(BuildContext context) {
    final scoreValue = attempt.scorePercent;
    final correctAnswers = attempt.correctAnswers;
    final scoreColor = (scoreValue ?? 0) >= 70
        ? AppColors.accent
        : (scoreValue ?? 0) >= 50
            ? Colors.orange
            : Colors.red;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.fromLTRB(12, 10, 12, 12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              attempt.quizTitle,
              style: const TextStyle(
                fontSize: 14,
                fontWeight: FontWeight.w700,
              ),
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Container(
                  padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                  decoration: BoxDecoration(
                    color: scoreColor.withValues(alpha: 0.15),
                    borderRadius: BorderRadius.circular(6),
                  ),
                  child: Text(
                    scoreValue == null
                        ? (attempt.isPracticeAttempt ? 'Practice' : 'Hidden')
                        : '${scoreValue.toStringAsFixed(0)}%',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w800,
                      color: scoreColor,
                    ),
                  ),
                ),
                if (attempt.isPracticeAttempt) ...[
                  const SizedBox(width: 8),
                  Container(
                    padding: const EdgeInsets.symmetric(vertical: 6, horizontal: 10),
                    decoration: BoxDecoration(
                      color: Colors.orange.withValues(alpha: 0.12),
                      borderRadius: BorderRadius.circular(6),
                    ),
                    child: Text(
                      'Practice Only',
                      style: TextStyle(
                        fontSize: 11,
                        fontWeight: FontWeight.w800,
                        color: Colors.orange.shade900,
                      ),
                    ),
                  ),
                ],
                const Spacer(),
                Text(
                  correctAnswers == null
                      ? '--/${attempt.totalItems}'
                      : '$correctAnswers/${attempt.totalItems}',
                  style: const TextStyle(fontSize: 12, fontWeight: FontWeight.w700),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Wrap(
              spacing: 12,
              runSpacing: 4,
              children: [
                Text(
                  'Taken: ${_formatDate(attempt.submittedAt)}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                ),
                Text(
                  'Status: ${_formatStatus(attempt.status)}',
                  style: const TextStyle(fontSize: 12, color: Colors.grey),
                ),
              ],
            ),
            Align(
              alignment: Alignment.centerRight,
              child: TextButton(
                onPressed: onTap,
                child: const Text('Open Attempt Review'),
              ),
            ),
            if (detailFuture == null)
              const Padding(
                padding: EdgeInsets.symmetric(vertical: 12),
                child: Center(child: CircularProgressIndicator()),
              )
            else
              FutureBuilder<AttemptDetailModel>(
                future: detailFuture,
                builder: (context, snapshot) {
                  if (snapshot.connectionState == ConnectionState.waiting) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Center(child: CircularProgressIndicator()),
                    );
                  }
                  if (snapshot.hasError) {
                    return const Padding(
                      padding: EdgeInsets.symmetric(vertical: 12),
                      child: Text(
                        'Failed to load attempt questions.',
                        style: TextStyle(color: Colors.red),
                      ),
                    );
                  }
                  if (!snapshot.hasData) {
                    return const SizedBox.shrink();
                  }

                  final detail = snapshot.data!;
                  return Column(
                    children: detail.questions.asMap().entries.map((entry) {
                      final index = entry.key + 1;
                      final question = entry.value;
                      return _QuestionHistoryCard(
                        questionNumber: index,
                        question: question,
                      );
                    }).toList(),
                  );
                },
              ),
          ],
        ),
      ),
    );
  }

  String _formatDate(DateTime? date) {
    if (date == null) {
      return 'Unknown';
    }

    try {
      return DateFormat('MMM dd, yyyy HH:mm').format(date);
    } catch (_) {
      return date.toString().substring(0, 10);
    }
  }

  String _formatStatus(String status) {
    if (status.isEmpty) {
      return 'Unknown';
    }

    return status
        .split('_')
        .map((part) => part.isEmpty
            ? part
            : '${part[0].toUpperCase()}${part.substring(1)}')
        .join(' ');
  }
}

class _QuestionHistoryCard extends StatelessWidget {
  final int questionNumber;
  final AttemptQuestionDetail question;

  const _QuestionHistoryCard({
    required this.questionNumber,
    required this.question,
  });

  @override
  Widget build(BuildContext context) {
    final isCorrect = question.isCorrect;
    final statusColor = isCorrect == true ? AppColors.accent : Colors.red;
    final userAnswer = _formatUserAnswer(question);
    final correctAnswer = _formatCorrectAnswer(question);

    return Card(
      margin: const EdgeInsets.only(bottom: 10),
      elevation: 0,
      color: Colors.grey[50],
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                Expanded(
                  child: Text(
                    'Q$questionNumber: ${question.questionText}',
                    style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w700),
                  ),
                ),
                if (isCorrect != null)
                  Text(
                    isCorrect ? 'Correct' : 'Incorrect',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: statusColor,
                    ),
                  )
                else
                  const Text(
                    'Review hidden',
                    style: TextStyle(
                      fontSize: 12,
                      fontWeight: FontWeight.w700,
                      color: AppColors.gray600,
                    ),
                  ),
              ],
            ),
            const SizedBox(height: 8),
            Text('Your Answer:', style: Theme.of(context).textTheme.labelMedium),
            const SizedBox(height: 4),
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: isCorrect == true
                    ? AppColors.accent.withValues(alpha: 0.1)
                    : isCorrect == false
                        ? Colors.red.withValues(alpha: 0.1)
                        : Colors.grey[100],
                borderRadius: BorderRadius.circular(8),
                border: Border.all(
                  color: isCorrect == true
                      ? AppColors.accent
                      : isCorrect == false
                          ? Colors.red
                          : Colors.grey[300]!,
                ),
              ),
              child: Text(
                userAnswer,
                style: TextStyle(
                  fontSize: 13,
                  fontWeight: FontWeight.w600,
                  color: isCorrect == true
                      ? AppColors.accent
                      : isCorrect == false
                          ? Colors.red
                          : null,
                ),
              ),
            ),
            if (correctAnswer != null) ...[
              const SizedBox(height: 8),
              Text('Correct Answer:', style: Theme.of(context).textTheme.labelMedium),
              const SizedBox(height: 4),
              Text(
                correctAnswer,
                style: const TextStyle(fontSize: 13, fontWeight: FontWeight.w600),
              ),
            ],
          ],
        ),
      ),
    );
  }

  String _formatUserAnswer(AttemptQuestionDetail question) {
    if (question.questionType == 'short_answer') {
      return question.textAnswer ?? '(No answer)';
    }
    if (question.questionType == 'ordering') {
      final selected = question.options.where((o) => o.isSelected).toList();
      if (selected.isEmpty) {
        return '(No answer)';
      }
      return selected.map((o) => o.text).join(' > ');
    }
    final selected = question.options.where((o) => o.isSelected).toList();
    if (selected.isEmpty) {
      return '(No answer)';
    }
    return selected.map((o) => o.text).join(', ');
  }

  String? _formatCorrectAnswer(AttemptQuestionDetail question) {
    if (question.questionType == 'ordering') {
      final ordered = question.options.where((o) => o.orderIndex != null).toList();
      if (ordered.isEmpty) {
        return null;
      }
      ordered.sort((a, b) => a.orderIndex!.compareTo(b.orderIndex!));
      return ordered.map((o) => o.text).join(' > ');
    }
    final correct = question.options.where((o) => o.isCorrect == true).toList();
    if (correct.isNotEmpty) {
      return correct.map((o) => o.text).join(', ');
    }
    if (question.correctOptionId != null) {
      final match = question.options.firstWhere(
        (o) => o.id == question.correctOptionId,
        orElse: () => const AttemptOption(id: 0, text: '', isSelected: false),
      );
      if (match.text.isNotEmpty) {
        return match.text;
      }
    }
    return null;
  }
}

class _QuizSummary {
  final int quizId;
  final String quizTitle;
  final String categoryName;
  final DateTime? latestAttemptAt;

  const _QuizSummary({
    required this.quizId,
    required this.quizTitle,
    required this.categoryName,
    required this.latestAttemptAt,
  });
}
