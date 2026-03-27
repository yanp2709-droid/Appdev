// lib/features/home/student/student_home_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../quiz/providers/quiz_provider.dart';

class StudentHomeScreen extends StatelessWidget {
  const StudentHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final user       = context.watch<AuthProvider>().user!;
    final quizProv   = context.watch<QuizProvider>();
    final hasResult  = quizProv.hasEverTakenQuiz;
    final lastResult = quizProv.lastResult;

    return Scaffold(
      // ── Task 5: Student drawer with Categories, Results, Logout ──────────
      drawer: _StudentDrawer(currentRoute: '/student-home'),
      body: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          // ── Blue header with menu button ─────────────────────────────────
          Container(
            padding: const EdgeInsets.fromLTRB(20, 52, 20, 36),
            decoration: const BoxDecoration(
              color: AppColors.primary,
              borderRadius:
                  BorderRadius.vertical(bottom: Radius.circular(32)),
            ),
            child: Row(
              children: [
                Expanded(
                  child: Text(
                    'Welcome, ${user.name}!',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 26,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                ),
                Builder(
                  builder: (ctx) => IconButton(
                    icon: const Icon(Icons.menu, color: Colors.white, size: 28),
                    onPressed: () => Scaffold.of(ctx).openDrawer(),
                  ),
                ),
              ],
            ),
          ),

          Expanded(
            child: Padding(
              padding: const EdgeInsets.all(20),
              child: Column(
                children: [
                  const SizedBox(height: 4),

                  // Score cards — only shown after student has taken a quiz (Task requirement)
                  if (hasResult) ...[
                    Row(
                      children: [
                        _ScoreCard(
                          label: 'Latest Score',
                          value: '${lastResult!.scorePercent}%',
                          color: AppColors.primary,
                        ),
                        const SizedBox(width: 12),
                        _ScoreCard(
                          label: 'Last Category',
                          value: lastResult.categoryName,
                          color: AppColors.accent,
                          smallText: true,
                        ),
                      ],
                    ),
                    const SizedBox(height: 16),
                  ],

                  // Browse Categories
                  SizedBox(
                    width: double.infinity, height: 50,
                    child: ElevatedButton(
                      onPressed: () => context.go('/categories'),
                      style: ElevatedButton.styleFrom(
                        backgroundColor: AppColors.primary,
                        shape: RoundedRectangleBorder(
                            borderRadius: BorderRadius.circular(12)),
                      ),
                      child: const Text('Browse Categories',
                          style: TextStyle(
                              color: Colors.white,
                              fontWeight: FontWeight.w700,
                              fontSize: 16)),
                    ),
                  ),

                  const Spacer(),

                  Consumer<AuthProvider>(
                    builder: (_, auth, __) => OutlinedButton(
                        onPressed: auth.status == AuthStatus.loading
                          ? null
                          : () async {
                              context.read<QuizProvider>().reset(clearLastResult: true);
                              await auth.logout();
                              if (context.mounted) context.go('/login');
                            },
                      child: auth.status == AuthStatus.loading
                          ? const SizedBox(
                              width: 20,
                              height: 20,
                              child: CircularProgressIndicator(
                                strokeWidth: 2,
                              ),
                            )
                          : const Text('Logout'),
                    ),
                  ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

// ── Task 5: Student drawer — Categories, Results, Logout ──────────────────────
class _StudentDrawer extends StatelessWidget {
  final String currentRoute;
  const _StudentDrawer({required this.currentRoute});

  @override
  Widget build(BuildContext context) {
    final auth = context.read<AuthProvider>();

    return Drawer(
      backgroundColor: AppColors.primaryDark,
      child: SafeArea(
        child: Column(
          children: [
            // Drawer header
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(20),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const CircleAvatar(
                    radius: 28,
                    backgroundColor: Colors.white24,
                    child: Icon(Icons.person, color: Colors.white, size: 32),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    auth.user?.name ?? '',
                    style: const TextStyle(
                      color: Colors.white,
                      fontSize: 16,
                      fontWeight: FontWeight.w700,
                    ),
                  ),
                  Text(
                    auth.user?.email ?? '',
                    style: const TextStyle(
                        color: Colors.white60, fontSize: 12),
                  ),
                ],
              ),
            ),
            const Divider(color: Colors.white24, height: 1),
            const SizedBox(height: 8),

            // Categories
            _DrawerTile(
              icon: Icons.category,
              label: 'Categories',
              isActive: currentRoute == '/categories',
              onTap: () {
                Navigator.pop(context);
                context.go('/categories');
              },
            ),

            // History (replaced Results)
            _DrawerTile(
              icon: Icons.history,
              label: 'History',
              isActive: currentRoute == '/history',
              onTap: () {
                Navigator.pop(context);
                context.go('/history');
              },
            ),

            const Spacer(),
            const Divider(color: Colors.white24, height: 1),

            // Logout
            Consumer<AuthProvider>(
              builder: (ctx, authProvider, _) => _DrawerTile(
                icon: Icons.logout,
                label: 'Logout',
                isActive: false,
                isLoading: authProvider.status == AuthStatus.loading,
                onTap: authProvider.status == AuthStatus.loading
                    ? null
                    : () async {
                        Navigator.pop(context);
                        context.read<QuizProvider>().reset(clearLastResult: true);
                        await authProvider.logout();
                        if (context.mounted) context.go('/login');
                      },
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }
}

class _DrawerTile extends StatelessWidget {
  final IconData icon;
  final String label;
  final bool isActive;
  final VoidCallback? onTap;
  final bool isLoading;

  const _DrawerTile({
    required this.icon,
    required this.label,
    required this.isActive,
    required this.onTap,
    this.isLoading = false,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
      decoration: BoxDecoration(
        color: isActive ? AppColors.primary : Colors.transparent,
        borderRadius: BorderRadius.circular(8),
      ),
      child: ListTile(
        leading: isLoading
            ? const SizedBox(
                width: 20,
                height: 20,
                child: CircularProgressIndicator(
                  strokeWidth: 2,
                  valueColor: AlwaysStoppedAnimation<Color>(Colors.white),
                ),
              )
            : Icon(icon, color: Colors.white, size: 20),
        title: Text(label,
            style: const TextStyle(
                color: Colors.white, fontWeight: FontWeight.w600)),
        onTap: isLoading ? null : onTap,
        dense: true,
      ),
    );
  }
}

// ── Score card widget ─────────────────────────────────────────────────────────
class _ScoreCard extends StatelessWidget {
  final String label;
  final String value;
  final Color color;
  final bool smallText;

  const _ScoreCard({
    required this.label,
    required this.value,
    required this.color,
    this.smallText = false,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
            color: color, borderRadius: BorderRadius.circular(14)),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(label,
                style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 12,
                    fontWeight: FontWeight.w600)),
            const SizedBox(height: 6),
            Text(
              value,
              style: TextStyle(
                color: Colors.white,
                fontSize: smallText ? 16 : 28,
                fontWeight: FontWeight.w800,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ),
      ),
    );
  }
}
