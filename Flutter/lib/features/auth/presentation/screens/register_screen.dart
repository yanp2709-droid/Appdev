// lib/features/auth/presentation/screens/register_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';

class RegisterScreen extends StatefulWidget {
  const RegisterScreen({super.key});

  @override
  State<RegisterScreen> createState() => _RegisterScreenState();
}

class _RegisterScreenState extends State<RegisterScreen>
    with TickerProviderStateMixin {
  final _formKey = GlobalKey<FormState>();

  final _firstNameController = TextEditingController();
  final _lastNameController = TextEditingController();
  final _emailController = TextEditingController();
  final _studentIdController = TextEditingController();
  final _sectionController = TextEditingController();
  final _yearLevelController = TextEditingController();
  final _courseController = TextEditingController();
  final _passwordController = TextEditingController();
  final _confirmPasswordController = TextEditingController();

  bool _privacyConsent = false;
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  String? _errorMessage;
  String? _successMessage;

  late AnimationController _fadeController;
  late Animation<double> _fadeAnimation;

  @override
  void initState() {
    super.initState();
    _fadeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _fadeAnimation = CurvedAnimation(
      parent: _fadeController,
      curve: Curves.easeOut,
    );
    _fadeController.forward();
  }

  @override
  void dispose() {
    _fadeController.dispose();
    _firstNameController.dispose();
    _lastNameController.dispose();
    _emailController.dispose();
    _studentIdController.dispose();
    _sectionController.dispose();
    _yearLevelController.dispose();
    _courseController.dispose();
    _passwordController.dispose();
    _confirmPasswordController.dispose();
    super.dispose();
  }

  String? _validateRequired(String? value, String fieldName) {
    if (value == null || value.trim().isEmpty) return '$fieldName is required';
    return null;
  }

  String? _validateEmail(String? value) {
    if (value == null || value.trim().isEmpty) return 'Email is required';
    final emailRegex = RegExp(r'^[\w.-]+@[\w.-]+\.\w{2,}$');
    if (!emailRegex.hasMatch(value.trim())) return 'Enter a valid email address';
    return null;
  }

  String? _validatePassword(String? value) {
    if (value == null || value.isEmpty) return 'Password is required';
    if (value.length < 8) return 'Password must be at least 8 characters';
    if (!RegExp(r'^(?=.*[a-zA-Z])(?=.*\d)').hasMatch(value)) {
      return 'Password must include letters and numbers';
    }
    return null;
  }

  String? _validateConfirmPassword(String? value) {
    if (value == null || value.isEmpty) return 'Please confirm your password';
    if (value != _passwordController.text) return 'Passwords do not match';
    return null;
  }

  String? _validateName(String? value, String fieldName) {
    if (value == null || value.trim().isEmpty) return '$fieldName is required';
    if (value.trim().length < 2) return '$fieldName is too short';
    return null;
  }

  Future<void> _handleRegister() async {
    setState(() {
      _errorMessage = null;
      _successMessage = null;
    });

    if (!_formKey.currentState!.validate()) return;

    if (!_privacyConsent) {
      setState(() {
        _errorMessage = 'You must agree to the Privacy Notice to continue.';
      });
      return;
    }

    setState(() => _isLoading = true);

    try {
      // TODO: Replace with your actual API endpoint
      // final response = await http.post(
      //   Uri.parse('https://your-api.com/api/register'),
      //   headers: {'Content-Type': 'application/json'},
      //   body: jsonEncode({
      //     'first_name': _firstNameController.text.trim(),
      //     'last_name': _lastNameController.text.trim(),
      //     'email': _emailController.text.trim(),
      //     'student_id': _studentIdController.text.trim(),
      //     'section': _sectionController.text.trim(),
      //     'year_level': _yearLevelController.text.trim(),
      //     'course': _courseController.text.trim(),
      //     'password': _passwordController.text,
      //     'password_confirmation': _confirmPasswordController.text,
      //     'privacy_consent': _privacyConsent,
      //   }),
      // );

      // Simulated network delay — remove when connecting real API
      await Future.delayed(const Duration(seconds: 2));

      // On success: show message then redirect to login
      setState(() {
        _successMessage = 'Account created successfully! Redirecting to login...';
      });

      await Future.delayed(const Duration(seconds: 2));

      if (mounted) {
        context.go('/login'); // ✅ redirect to login after register
      }
    } catch (e) {
      setState(() {
        _errorMessage = 'Something went wrong. Please try again.';
      });
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.primary,
      body: SafeArea(
        child: Center(
          child: FadeTransition(
            opacity: _fadeAnimation,
            child: SingleChildScrollView(
              padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 32),
              child: Column(
                children: [
                  // ── Logo ───────────────────────────────────────────────
                  Container(
                    width: 72,
                    height: 72,
                    decoration: BoxDecoration(
                      color: Colors.white.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(18),
                    ),
                    child: const Center(
                      child: Text('💬', style: TextStyle(fontSize: 36)),
                    ),
                  ),
                  const SizedBox(height: 14),
                  const Text(
                    'TechQuiz',
                    style: TextStyle(
                      color: Colors.white,
                      fontSize: 28,
                      fontWeight: FontWeight.w800,
                    ),
                  ),
                  const SizedBox(height: 6),
                  const Text(
                    'Create your student account',
                    textAlign: TextAlign.center,
                    style: TextStyle(color: Colors.white70, fontSize: 13),
                  ),
                  const SizedBox(height: 28),

                  // ── Form card ──────────────────────────────────────────
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
                          // Error / Success banners
                          if (_errorMessage != null)
                            _StatusBanner(message: _errorMessage!, isError: true),
                          if (_successMessage != null)
                            _StatusBanner(message: _successMessage!, isError: false),

                          // First Name & Last Name
                          Row(
                            children: [
                              Expanded(
                                child: _buildField(
                                  controller: _firstNameController,
                                  hint: 'First Name',
                                  validator: (v) => _validateName(v, 'First name'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _lastNameController,
                                  hint: 'Last Name',
                                  validator: (v) => _validateName(v, 'Last name'),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Email
                          _buildField(
                            controller: _emailController,
                            hint: 'School Email',
                            keyboardType: TextInputType.emailAddress,
                            validator: _validateEmail,
                          ),
                          const SizedBox(height: 12),

                          // Student ID & Section
                          Row(
                            children: [
                              Expanded(
                                child: _buildField(
                                  controller: _studentIdController,
                                  hint: 'Student ID',
                                  validator: (v) => _validateRequired(v, 'Student ID'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _sectionController,
                                  hint: 'Section',
                                  validator: (v) => _validateRequired(v, 'Section'),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Year Level & Course
                          Row(
                            children: [
                              Expanded(
                                child: _buildField(
                                  controller: _yearLevelController,
                                  hint: 'Year Level',
                                  keyboardType: TextInputType.number,
                                  validator: (v) => _validateRequired(v, 'Year level'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _courseController,
                                  hint: 'Course / Program',
                                  validator: (v) => _validateRequired(v, 'Course'),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 12),

                          // Password
                          _buildField(
                            controller: _passwordController,
                            hint: 'Password',
                            obscureText: _obscurePassword,
                            validator: _validatePassword,
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscurePassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                color: Colors.grey,
                                size: 20,
                              ),
                              onPressed: () => setState(
                                  () => _obscurePassword = !_obscurePassword),
                            ),
                          ),
                          const SizedBox(height: 12),

                          // Confirm Password
                          _buildField(
                            controller: _confirmPasswordController,
                            hint: 'Confirm Password',
                            obscureText: _obscureConfirmPassword,
                            validator: _validateConfirmPassword,
                            suffixIcon: IconButton(
                              icon: Icon(
                                _obscureConfirmPassword
                                    ? Icons.visibility_off
                                    : Icons.visibility,
                                color: Colors.grey,
                                size: 20,
                              ),
                              onPressed: () => setState(() =>
                                  _obscureConfirmPassword =
                                      !_obscureConfirmPassword),
                            ),
                          ),
                          const SizedBox(height: 16),

                          // Privacy consent
                          Row(
                            children: [
                              SizedBox(
                                width: 24,
                                height: 24,
                                child: Checkbox(
                                  value: _privacyConsent,
                                  onChanged: (v) =>
                                      setState(() => _privacyConsent = v ?? false),
                                  activeColor: AppColors.danger,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Flexible(
                                child: GestureDetector(
                                  onTap: () => setState(
                                      () => _privacyConsent = !_privacyConsent),
                                  child: const Text(
                                    'I agree to the Privacy Notice',
                                    style: TextStyle(
                                      color: Colors.black87,
                                      fontSize: 13,
                                    ),
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 20),

                          // Create Account button
                          SizedBox(
                            height: 48,
                            child: ElevatedButton(
                              onPressed: _isLoading ? null : _handleRegister,
                              style: ElevatedButton.styleFrom(
                                backgroundColor: AppColors.danger,
                                foregroundColor: Colors.white,
                                disabledBackgroundColor:
                                    AppColors.danger.withOpacity(0.5),
                                shape: RoundedRectangleBorder(
                                  borderRadius: BorderRadius.circular(10),
                                ),
                                elevation: 2,
                              ),
                              child: _isLoading
                                  ? const SizedBox(
                                      width: 20,
                                      height: 20,
                                      child: CircularProgressIndicator(
                                        color: Colors.white,
                                        strokeWidth: 2.5,
                                      ),
                                    )
                                  : const Text(
                                      'Create Account',
                                      style: TextStyle(
                                        fontSize: 15,
                                        fontWeight: FontWeight.bold,
                                      ),
                                    ),
                            ),
                          ),
                        ],
                      ),
                    ),
                  ),
                  const SizedBox(height: 16),

                  // ── Sign in link ───────────────────────────────────────
                  Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      const Text(
                        'Already have an account? ',
                        style: TextStyle(color: Colors.white70, fontSize: 13),
                      ),
                      GestureDetector(
                        onTap: () => context.go('/login'), // ✅ go back to login
                        child: const Text(
                          'Sign in',
                          style: TextStyle(
                            color: Colors.white,
                            fontSize: 13,
                            fontWeight: FontWeight.bold,
                            decoration: TextDecoration.underline,
                            decorationColor: Colors.white,
                          ),
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String hint,
    TextInputType keyboardType = TextInputType.text,
    bool obscureText = false,
    String? Function(String?)? validator,
    Widget? suffixIcon,
  }) {
    return TextFormField(
      controller: controller,
      keyboardType: keyboardType,
      obscureText: obscureText,
      style: const TextStyle(fontSize: 14, color: Colors.black87),
      validator: validator,
      decoration: InputDecoration(
        hintText: hint,
        hintStyle: const TextStyle(color: Colors.black38, fontSize: 13),
        suffixIcon: suffixIcon,
        contentPadding:
            const EdgeInsets.symmetric(horizontal: 14, vertical: 14),
        filled: true,
        fillColor: const Color(0xFFF5F5F5),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide.none,
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: const BorderSide(color: Color(0xFFE0E0E0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: AppColors.primary, width: 1.5),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: AppColors.danger, width: 1.5),
        ),
        focusedErrorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(10),
          borderSide: BorderSide(color: AppColors.danger, width: 1.5),
        ),
        errorStyle: TextStyle(color: AppColors.danger, fontSize: 11),
      ),
    );
  }
}

class _StatusBanner extends StatelessWidget {
  final String message;
  final bool isError;

  const _StatusBanner({required this.message, required this.isError});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 14),
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
      decoration: BoxDecoration(
        color: isError
            ? AppColors.dangerBg
            : Colors.green.shade50,
        borderRadius: BorderRadius.circular(8),
        border: Border.all(
          color: isError ? AppColors.danger : Colors.green,
        ),
      ),
      child: Row(
        children: [
          Icon(
            isError ? Icons.warning_amber_rounded : Icons.check_circle_outline,
            color: isError ? AppColors.danger : Colors.green,
            size: 18,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              message,
              style: TextStyle(
                color: isError ? AppColors.danger : Colors.green.shade800,
                fontSize: 12,
                fontWeight: FontWeight.w500,
              ),
            ),
          ),
        ],
      ),
    );
  }
}