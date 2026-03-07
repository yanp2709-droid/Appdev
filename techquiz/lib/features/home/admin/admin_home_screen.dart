// lib/features/home/admin/admin_home_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../core/constants/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import '../../admin/providers/admin_questions_provider.dart';

class AdminHomeScreen extends StatelessWidget {
  const AdminHomeScreen({super.key});

  @override
  Widget build(BuildContext context) {
    final provider = context.watch<AdminQuestionsProvider>();
    final totalQ   = provider.allQuestions.length;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Admin Dashboard'),
        leading: Builder(
          builder: (ctx) => IconButton(
            icon: const Icon(Icons.menu),
            onPressed: () => Scaffold.of(ctx).openDrawer(),
          ),
        ),
      ),
      drawer: const AdminDrawerWidget(currentRoute: '/admin-home'),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('Welcome, Admin!',
                style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textDark)),
            const SizedBox(height: 16),

            // Stats cards
            Row(
              children: [
                _StatCard(
                  label: 'Total Questions',
                  value: '$totalQ',
                  icon: Icons.quiz,
                  color: AppColors.primary,
                ),
                const SizedBox(width: 12),
                _StatCard(
                  label: 'Categories',
                  value: '4',
                  icon: Icons.category,
                  color: AppColors.accent,
                ),
              ],
            ),
            const SizedBox(height: 24),

            const Text('Quick Actions',
                style: TextStyle(
                    fontSize: 15,
                    fontWeight: FontWeight.w700,
                    color: AppColors.textDark)),
            const SizedBox(height: 12),

            // Manage Questions
            _ActionTile(
              icon: Icons.quiz,
              title: 'Manage Questions',
              subtitle: 'Add, edit or delete questions per category',
              color: AppColors.primary,
              onTap: () => context.go('/categories'),
            ),
            const SizedBox(height: 10),

            // Students
            _ActionTile(
              icon: Icons.people,
              title: 'Students',
              subtitle: 'View student activity and scores',
              color: AppColors.accent,
              onTap: () {},
            ),
          ],
        ),
      ),
    );
  }
}

// ── Stat card ────────────────────────────────────────────────────────────────
class _StatCard extends StatelessWidget {
  final String label;
  final String value;
  final IconData icon;
  final Color color;

  const _StatCard({
    required this.label, required this.value,
    required this.icon, required this.color,
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
            Icon(icon, color: Colors.white70, size: 20),
            const SizedBox(height: 8),
            Text(value,
                style: const TextStyle(
                    color: Colors.white,
                    fontSize: 28,
                    fontWeight: FontWeight.w900)),
            Text(label,
                style: const TextStyle(
                    color: Colors.white70,
                    fontSize: 11,
                    fontWeight: FontWeight.w600)),
          ],
        ),
      ),
    );
  }
}

// ── Action tile ──────────────────────────────────────────────────────────────
class _ActionTile extends StatelessWidget {
  final IconData icon;
  final String title;
  final String subtitle;
  final Color color;
  final VoidCallback onTap;

  const _ActionTile({
    required this.icon, required this.title,
    required this.subtitle, required this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.all(16),
        decoration: BoxDecoration(
          color: Colors.white,
          borderRadius: BorderRadius.circular(14),
          border: Border.all(color: AppColors.gray200),
          boxShadow: [
            BoxShadow(
                color: Colors.black.withOpacity(0.04),
                blurRadius: 8,
                offset: const Offset(0, 2))
          ],
        ),
        child: Row(
          children: [
            Container(
              width: 44, height: 44,
              decoration: BoxDecoration(
                color: color.withOpacity(0.12),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color, size: 22),
            ),
            const SizedBox(width: 14),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(title,
                      style: const TextStyle(
                          fontWeight: FontWeight.w700,
                          fontSize: 14,
                          color: AppColors.textDark)),
                  const SizedBox(height: 2),
                  Text(subtitle,
                      style: const TextStyle(
                          fontSize: 12, color: AppColors.gray600)),
                ],
              ),
            ),
            const Icon(Icons.arrow_forward_ios,
                size: 14, color: AppColors.gray400),
          ],
        ),
      ),
    );
  }
}

// ── Task 5: Shared Admin Drawer — Dashboard, Categories, Admin Panel, Logout ──
class AdminDrawerWidget extends StatelessWidget {
  final String currentRoute;
  const AdminDrawerWidget({super.key, required this.currentRoute});

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
                    child: Icon(Icons.admin_panel_settings,
                        color: Colors.white, size: 30),
                  ),
                  const SizedBox(height: 10),
                  Text(
                    auth.user?.name ?? 'Admin',
                    style: const TextStyle(
                        color: Colors.white,
                        fontSize: 16,
                        fontWeight: FontWeight.w700),
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

            // Dashboard
            _DrawerTile(
              icon: Icons.dashboard,
              label: 'Dashboard',
              isActive: currentRoute == '/admin-home',
              onTap: () {
                Navigator.pop(context);
                context.go('/admin-home');
              },
            ),

            // Categories / Questions
            _DrawerTile(
              icon: Icons.category,
              label: 'Categories',
              isActive: currentRoute == '/categories',
              onTap: () {
                Navigator.pop(context);
                context.go('/categories');
              },
            ),

            // Task 5: Admin Panel link
            _DrawerTile(
              icon: Icons.open_in_new,
              label: 'Admin Panel',
              isActive: false,
              onTap: () {
                Navigator.pop(context);
                // External admin panel link placeholder
                // Replace with launchUrl(Uri.parse('https://admin.techquiz.com'))
                // when url_launcher package is added
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(
                    content: Text('Admin Panel: connect to external URL'),
                    backgroundColor: AppColors.primary,
                  ),
                );
              },
            ),

            const Spacer(),
            const Divider(color: Colors.white24, height: 1),

            // Logout
            _DrawerTile(
              icon: Icons.logout,
              label: 'Logout',
              isActive: false,
              onTap: () async {
                Navigator.pop(context);
                await auth.logout();
                if (context.mounted) context.go('/login');
              },
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
  final VoidCallback onTap;

  const _DrawerTile({
    required this.icon, required this.label,
    required this.isActive, required this.onTap,
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
        leading: Icon(icon, color: Colors.white, size: 20),
        title: Text(label,
            style: const TextStyle(
                color: Colors.white, fontWeight: FontWeight.w600)),
        onTap: onTap,
        dense: true,
      ),
    );
  }
}
