// lib/features/admin/presentation/screens/admin_questions_screen.dart
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../home/admin/admin_home_screen.dart';
import '../../providers/admin_questions_provider.dart';
import '../../../quiz/data/question_model.dart';
import '../../../categories/data/category_model.dart';

// Convert to StatefulWidget so we can hold a direct reference to the provider
class AdminQuestionsScreen extends StatefulWidget {
  final CategoryModel category;
  const AdminQuestionsScreen({super.key, required this.category});

  @override
  State<AdminQuestionsScreen> createState() => _AdminQuestionsScreenState();
}

class _AdminQuestionsScreenState extends State<AdminQuestionsScreen> {
  // Hold provider reference directly — avoids context issues inside dialogs
  late AdminQuestionsProvider _provider;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    _provider = context.read<AdminQuestionsProvider>();
  }

  void _showAddDialog() {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => _QuestionFormDialog(
        categoryId: widget.category.id,
        onSave: (q) {
          _provider.addQuestion(q);
        },
      ),
    );
  }

  void _showEditDialog(QuestionModel q) {
    showDialog(
      context: context,
      barrierDismissible: false,
      builder: (_) => _QuestionFormDialog(
        categoryId: widget.category.id,
        existing: q,
        onSave: (updated) {
          _provider.updateQuestion(updated);
        },
      ),
    );
  }

  void _confirmDelete(QuestionModel q) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('Delete Question?'),
        content: Text(
          '"${q.questionText}"',
          maxLines: 2,
          overflow: TextOverflow.ellipsis,
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Cancel'),
          ),
          TextButton(
            onPressed: () {
              _provider.deleteQuestion(q.id);
              Navigator.pop(context);
            },
            child: const Text(
              'Delete',
              style: TextStyle(color: AppColors.danger),
            ),
          ),
        ],
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('${widget.category.name} — Questions'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => Navigator.pop(context),
        ),
      ),
      drawer: const AdminDrawerWidget(currentRoute: '/admin-home'),
      floatingActionButton: FloatingActionButton.extended(
        onPressed: _showAddDialog,
        backgroundColor: AppColors.primary,
        icon: const Icon(Icons.add, color: Colors.white),
        label: const Text(
          'Add Question',
          style: TextStyle(color: Colors.white, fontWeight: FontWeight.w700),
        ),
      ),
      body: Consumer<AdminQuestionsProvider>(
        builder: (context, provider, _) {
          final questions = provider.getByCategory(widget.category.id);

          if (questions.isEmpty) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Text('📝', style: TextStyle(fontSize: 64)),
                  const SizedBox(height: 16),
                  const Text(
                    'No questions yet',
                    style: TextStyle(
                      fontSize: 18,
                      fontWeight: FontWeight.w700,
                      color: AppColors.textDark,
                    ),
                  ),
                  const SizedBox(height: 8),
                  const Text(
                    'Tap + Add Question to get started.',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: AppColors.gray600, fontSize: 13),
                  ),
                ],
              ),
            );
          }

          return ListView.builder(
            padding: const EdgeInsets.fromLTRB(16, 16, 16, 100),
            itemCount: questions.length,
            itemBuilder: (_, i) => _QuestionCard(
              question: questions[i],
              index: i,
              onEdit: () => _showEditDialog(questions[i]),
              onDelete: () => _confirmDelete(questions[i]),
            ),
          );
        },
      ),
    );
  }
}

// ── Question card ─────────────────────────────────────────────────────────────
class _QuestionCard extends StatelessWidget {
  final QuestionModel question;
  final int           index;
  final VoidCallback  onEdit;
  final VoidCallback  onDelete;

  const _QuestionCard({
    required this.question,
    required this.index,
    required this.onEdit,
    required this.onDelete,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(14),
        border: Border.all(color: AppColors.gray200),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withOpacity(0.04),
            blurRadius: 8,
            offset: const Offset(0, 2),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Container(
                  width: 28, height: 28,
                  decoration: BoxDecoration(
                    color: AppColors.primary.withOpacity(0.1),
                    shape: BoxShape.circle,
                  ),
                  child: Center(
                    child: Text(
                      '${index + 1}',
                      style: const TextStyle(
                        fontSize: 12,
                        fontWeight: FontWeight.w800,
                        color: AppColors.primary,
                      ),
                    ),
                  ),
                ),
                const SizedBox(width: 10),
                Expanded(
                  child: Text(
                    question.questionText,
                    style: const TextStyle(
                      fontWeight: FontWeight.w700,
                      fontSize: 14,
                      color: AppColors.textDark,
                    ),
                  ),
                ),
                PopupMenuButton<String>(
                  onSelected: (v) {
                    if (v == 'edit') onEdit();
                    if (v == 'delete') onDelete();
                  },
                  itemBuilder: (_) => [
                    const PopupMenuItem(
                      value: 'edit',
                      child: Row(
                        children: [
                          Icon(Icons.edit, size: 18, color: AppColors.primary),
                          SizedBox(width: 8),
                          Text('Edit'),
                        ],
                      ),
                    ),
                    const PopupMenuItem(
                      value: 'delete',
                      child: Row(
                        children: [
                          Icon(Icons.delete, size: 18, color: AppColors.danger),
                          SizedBox(width: 8),
                          Text('Delete',
                              style: TextStyle(color: AppColors.danger)),
                        ],
                      ),
                    ),
                  ],
                  child: const Icon(Icons.more_vert,
                      color: AppColors.gray400, size: 20),
                ),
              ],
            ),
            const SizedBox(height: 12),

            ...List.generate(question.options.length, (i) {
              final isCorrect = i == question.correctIndex;
              return Container(
                margin: const EdgeInsets.only(bottom: 6),
                padding:
                    const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
                decoration: BoxDecoration(
                  color: isCorrect
                      ? Colors.green.withOpacity(0.1)
                      : AppColors.gray100,
                  borderRadius: BorderRadius.circular(8),
                  border: Border.all(
                    color: isCorrect
                        ? Colors.green.withOpacity(0.4)
                        : Colors.transparent,
                  ),
                ),
                child: Row(
                  children: [
                    Text(
                      ['A', 'B', 'C', 'D'][i],
                      style: TextStyle(
                        fontWeight: FontWeight.w800,
                        fontSize: 12,
                        color: isCorrect ? Colors.green : AppColors.gray600,
                      ),
                    ),
                    const SizedBox(width: 8),
                    Expanded(
                      child: Text(
                        question.options[i],
                        style: TextStyle(
                          fontSize: 13,
                          color: isCorrect
                              ? Colors.green.shade700
                              : AppColors.textDark,
                          fontWeight: isCorrect
                              ? FontWeight.w600
                              : FontWeight.w400,
                        ),
                      ),
                    ),
                    if (isCorrect)
                      const Icon(Icons.check_circle,
                          color: Colors.green, size: 16),
                  ],
                ),
              );
            }),
          ],
        ),
      ),
    );
  }
}

// ── Add / Edit dialog ─────────────────────────────────────────────────────────
class _QuestionFormDialog extends StatefulWidget {
  final int            categoryId;
  final QuestionModel? existing;
  final void Function(QuestionModel) onSave;

  const _QuestionFormDialog({
    required this.categoryId,
    this.existing,
    required this.onSave,
  });

  @override
  State<_QuestionFormDialog> createState() => _QuestionFormDialogState();
}

class _QuestionFormDialogState extends State<_QuestionFormDialog> {
  final _formKey      = GlobalKey<FormState>();
  final _questionCtrl = TextEditingController();
  final _optionCtrls  = List.generate(4, (_) => TextEditingController());
  int   _correctIndex = 0;

  @override
  void initState() {
    super.initState();
    if (widget.existing != null) {
      final q = widget.existing!;
      _questionCtrl.text = q.questionText;
      for (int i = 0; i < 4; i++) {
        _optionCtrls[i].text = i < q.options.length ? q.options[i] : '';
      }
      _correctIndex = q.correctIndex;
    }
  }

  @override
  void dispose() {
    _questionCtrl.dispose();
    for (final c in _optionCtrls) c.dispose();
    super.dispose();
  }

  void _save() {
    if (!_formKey.currentState!.validate()) return;
    final q = QuestionModel(
      id: widget.existing?.id ??
          DateTime.now().millisecondsSinceEpoch.toString(),
      categoryId:   widget.categoryId,
      questionText: _questionCtrl.text.trim(),
      options:      _optionCtrls.map((c) => c.text.trim()).toList(),
      correctIndex: _correctIndex,
    );
    widget.onSave(q);
    Navigator.pop(context);
  }

  @override
  Widget build(BuildContext context) {
    final isEdit = widget.existing != null;
    return AlertDialog(
      title: Text(isEdit ? 'Edit Question' : 'Add Question'),
      scrollable: true,
      content: Form(
        key: _formKey,
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            TextFormField(
              controller: _questionCtrl,
              maxLines: 3,
              decoration: const InputDecoration(
                labelText: 'Question',
                hintText: 'Enter the question...',
              ),
              validator: (v) =>
                  v == null || v.trim().isEmpty ? 'Question is required' : null,
            ),
            const SizedBox(height: 16),
            const Text(
              'Options (tap radio to mark correct answer):',
              style: TextStyle(
                fontWeight: FontWeight.w600,
                fontSize: 13,
                color: AppColors.gray600,
              ),
            ),
            const SizedBox(height: 8),

            ...List.generate(4, (i) {
              return Row(
                children: [
                  Radio<int>(
                    value: i,
                    groupValue: _correctIndex,
                    onChanged: (v) => setState(() => _correctIndex = v!),
                    activeColor: AppColors.primary,
                  ),
                  Expanded(
                    child: TextFormField(
                      controller: _optionCtrls[i],
                      decoration: InputDecoration(
                        labelText: 'Option ${['A', 'B', 'C', 'D'][i]}',
                        suffixIcon: _correctIndex == i
                            ? const Icon(Icons.check_circle,
                                color: Colors.green, size: 18)
                            : null,
                      ),
                      validator: (v) => v == null || v.trim().isEmpty
                          ? 'Required'
                          : null,
                    ),
                  ),
                ],
              );
            }),
          ],
        ),
      ),
      actions: [
        TextButton(
          onPressed: () => Navigator.pop(context),
          child: const Text('Cancel'),
        ),
        ElevatedButton(
          onPressed: _save,
          style: ElevatedButton.styleFrom(
            backgroundColor: AppColors.primary,
          ),
          child: Text(
            isEdit ? 'Save Changes' : 'Add Question',
            style: const TextStyle(color: Colors.white),
          ),
        ),
      ],
    );
  }
}
