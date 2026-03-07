import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../providers/auth_provider.dart';

class SplashScreen extends StatefulWidget {
  const SplashScreen({super.key});

  @override
  State<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends State<SplashScreen> {
  @override
  void initState() {
    super.initState();
    _bootstrap();
  }

  Future<void> _bootstrap() async {
    final auth = context.read<AuthProvider>();
    await auth.init();
    if (!mounted) return;

    if (auth.isLoggedIn) {
      final role = auth.user!.role;
      context.go(role == 'admin' ? '/admin-home' : '/student-home');
    } else {
      context.go('/login');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              width: 80, height: 80,
              decoration: BoxDecoration(
                color: Colors.white.withOpacity(0.15),
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Center(
                child: Text('💬', style: TextStyle(fontSize: 40)),
              ),
            ),
            const SizedBox(height: 20),
            const Text(
              'TechQuiz',
              style: TextStyle(
                color: Colors.white, fontSize: 32,
                fontWeight: FontWeight.w800, letterSpacing: -0.5,
              ),
            ),
            const SizedBox(height: 6),
            const Text(
              'A Category-Based\nMobile Quiz Application',
              textAlign: TextAlign.center,
              style: TextStyle(color: Colors.white70, fontSize: 14),
            ),
            const SizedBox(height: 48),
            const Text(
              'Signing you in...',
              style: TextStyle(color: Colors.white54, fontSize: 14),
            ),
            const SizedBox(height: 16),
            const SizedBox(
              width: 36, height: 36,
              child: CircularProgressIndicator(
                color: Colors.white, strokeWidth: 3,
              ),
            ),
          ],
        ),
      ),
    );
  }
}
