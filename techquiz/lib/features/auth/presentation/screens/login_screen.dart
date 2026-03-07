// lib/features/auth/presentation/screens/login_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../../../core/utils/validators.dart';
import '../../providers/auth_provider.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final _formKey      = GlobalKey<FormState>();
  final _emailCtrl    = TextEditingController();
  final _passwordCtrl = TextEditingController();

  @override
  void dispose() {
    _emailCtrl.dispose();
    _passwordCtrl.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    final auth    = context.read<AuthProvider>();
    final success = await auth.login(
      _emailCtrl.text.trim(),
      _passwordCtrl.text,
    );
    if (!mounted) return;
    if (success) {
      context.go('/student-home');
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: SafeArea(
        child: Center(
          child: SingleChildScrollView(
            padding: const EdgeInsets.symmetric(horizontal: 24),
            child: Column(
              children: [
                // ── Logo ──────────────────────────────────────────────────
                Container(
                  width: 72, height: 72,
                  decoration: BoxDecoration(
                    color: Colors.white.withOpacity(0.15),
                    borderRadius: BorderRadius.circular(18),
                  ),
                  child: const Center(
                    child: Text('💬', style: TextStyle(fontSize: 36)),
                  ),
                ),
                const SizedBox(height: 14),
                const Text('TechQuiz',
                    style: TextStyle(
                      color: Colors.white, fontSize: 28,
                      fontWeight: FontWeight.w800,
                    )),
                const SizedBox(height: 6),
                const Text(
                  'A Category-Based\nMobile Quiz Application',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.white70, fontSize: 13),
                ),
                const SizedBox(height: 28),

                // ── Form card ────────────────────────────────────────────
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(16),
                  ),
                  child: Form(
                    key: _formKey,
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.stretch,
                      children: [
                        // Using AppTextField from app_widgets (Task 4)
                        AppTextField(
                          key: const Key('emailField'),
                          hint: 'Email',
                          controller: _emailCtrl,
                          keyboardType: TextInputType.emailAddress,
                          validator: Validators.email,
                        ),
                        const SizedBox(height: 12),
                        AppTextField(
                          key: const Key('passwordField'),
                          hint: 'Password',
                          controller: _passwordCtrl,
                          obscureText: true,
                          validator: Validators.password,
                        ),
                        const SizedBox(height: 16),

                        // Using PrimaryButton from app_widgets (Task 4)
                        Consumer<AuthProvider>(
                          builder: (_, auth, __) => PrimaryButton(
                            key: const Key('loginButton'),
                            label: 'Login',
                            backgroundColor: AppColors.danger,
                            isLoading: auth.status == AuthStatus.loading,
                            onPressed: _submit,
                          ),
                        ),

                        // Error state
                        Consumer<AuthProvider>(
                          builder: (_, auth, __) {
                            if (auth.status != AuthStatus.error ||
                                auth.errorMessage == null) {
                              return const SizedBox.shrink();
                            }
                            return Container(
                              margin: const EdgeInsets.only(top: 12),
                              padding: const EdgeInsets.all(12),
                              decoration: BoxDecoration(
                                color: AppColors.dangerBg,
                                borderRadius: BorderRadius.circular(8),
                                border: Border.all(color: AppColors.danger),
                              ),
                              child: Row(
                                crossAxisAlignment: CrossAxisAlignment.start,
                                children: [
                                  const Icon(Icons.warning_amber_rounded,
                                      color: AppColors.danger, size: 20),
                                  const SizedBox(width: 8),
                                  const Expanded(
                                    child: Column(
                                      crossAxisAlignment:
                                          CrossAxisAlignment.start,
                                      children: [
                                        Text('Invalid credentials',
                                            style: TextStyle(
                                              color: AppColors.danger,
                                              fontWeight: FontWeight.w700,
                                              fontSize: 13,
                                            )),
                                        Text('Incorrect password',
                                            style: TextStyle(
                                              color: AppColors.danger,
                                              fontSize: 12,
                                            )),
                                      ],
                                    ),
                                  ),
                                ],
                              ),
                            );
                          },
                        ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),
                const Text(
                  'Demo: alex@student.com / password123',
                  textAlign: TextAlign.center,
                  style: TextStyle(color: Colors.white54, fontSize: 11),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}
