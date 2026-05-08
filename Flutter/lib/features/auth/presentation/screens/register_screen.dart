// lib/features/auth/presentation/screens/register_screen.dart
import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../../core/constants/app_colors.dart';
import '../../../../core/exceptions/api_exception.dart';
import '../../../../core/widgets/app_widgets.dart';
import '../../../../services/auth_service.dart';

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

  bool _termsAccepted = false;
  bool _termsDialogAgreed = false;
  bool _isLoading = false;
  bool _obscurePassword = true;
  bool _obscureConfirmPassword = true;
  String? _errorMessage;
  String? _successMessage;
  final Map<String, String?> _fieldErrors = {};
  final AuthService _authService = AuthService();

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
    if (!emailRegex.hasMatch(value.trim())) {
      return 'Enter a valid email address';
    }
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
      _fieldErrors.clear();
    });

    if (!_formKey.currentState!.validate()) return;

    if (!_termsAccepted) {
      setState(() {
        _errorMessage =
            'You must agree to the Terms and Conditions before registering.';
      });
      return;
    }

    setState(() => _isLoading = true);

    try {
      await _authService.registerStudent(
        firstName: _firstNameController.text.trim(),
        lastName: _lastNameController.text.trim(),
        email: _emailController.text.trim(),
        studentId: _studentIdController.text.trim(),
        section: _sectionController.text.trim(),
        yearLevel: _yearLevelController.text.trim(),
        course: _courseController.text.trim(),
        password: _passwordController.text,
        passwordConfirmation: _confirmPasswordController.text,
        termsAccepted: _termsAccepted,
      );

      // On success: show message then redirect to login
      setState(() {
        _successMessage =
            'Account created successfully! Redirecting to login...';
      });

      await Future.delayed(const Duration(seconds: 2));

      if (mounted) {
        context.go('/login'); // redirect to login after register
      }
    } on ApiValidationException catch (e) {
      setState(() {
        if (e.fieldErrors.isNotEmpty) {
          e.fieldErrors.forEach((key, value) {
            if (key == 'privacy_consent') {
              _errorMessage =
                  'You must agree to the Terms and Conditions before registering.';
              return;
            }
            final message = value.isNotEmpty ? value.first : 'Invalid value';
            _fieldErrors[key] = message;
            if (key == 'password') {
              _fieldErrors['password_confirmation'] ??= message;
            }
          });
        } else {
          _errorMessage = e.message;
        }
      });
    } on ApiException catch (e) {
      setState(() {
        _errorMessage = e.message;
      });
    } catch (_) {
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
                      color: Colors.white.withValues(alpha: 0.15),
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
                            _StatusBanner(
                                message: _errorMessage!, isError: true),
                          if (_successMessage != null)
                            _StatusBanner(
                                message: _successMessage!, isError: false),

                          // First Name & Last Name
                          Row(
                            children: [
                              Expanded(
                                child: _buildField(
                                  controller: _firstNameController,
                                  hint: 'First Name',
                                  validator: (v) =>
                                      _validateName(v, 'First name'),
                                  errorText: _fieldErrors['first_name'],
                                  onChanged: (_) =>
                                      _clearFieldError('first_name'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _lastNameController,
                                  hint: 'Last Name',
                                  validator: (v) =>
                                      _validateName(v, 'Last name'),
                                  errorText: _fieldErrors['last_name'],
                                  onChanged: (_) =>
                                      _clearFieldError('last_name'),
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
                            errorText: _fieldErrors['email'],
                            onChanged: (_) => _clearFieldError('email'),
                          ),
                          const SizedBox(height: 12),

                          // Student ID & Section
                          Row(
                            children: [
                              Expanded(
                                child: _buildField(
                                  controller: _studentIdController,
                                  hint: 'Student ID',
                                  validator: (v) =>
                                      _validateRequired(v, 'Student ID'),
                                  errorText: _fieldErrors['student_id'],
                                  onChanged: (_) =>
                                      _clearFieldError('student_id'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _sectionController,
                                  hint: 'Section',
                                  validator: (v) =>
                                      _validateRequired(v, 'Section'),
                                  errorText: _fieldErrors['section'],
                                  onChanged: (_) => _clearFieldError('section'),
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
                                  validator: (v) =>
                                      _validateRequired(v, 'Year level'),
                                  errorText: _fieldErrors['year_level'],
                                  onChanged: (_) =>
                                      _clearFieldError('year_level'),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: _buildField(
                                  controller: _courseController,
                                  hint: 'Course / Program',
                                  validator: (v) =>
                                      _validateRequired(v, 'Course'),
                                  errorText: _fieldErrors['course'],
                                  onChanged: (_) => _clearFieldError('course'),
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
                            errorText: _fieldErrors['password'],
                            onChanged: (_) => _clearFieldError('password'),
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
                            errorText: _fieldErrors['password_confirmation'],
                            onChanged: (_) =>
                                _clearFieldError('password_confirmation'),
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

                          Row(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              SizedBox(
                                width: 24,
                                height: 24,
                                child: Checkbox(
                                  value: _termsAccepted,
                                  onChanged: (v) => setState(
                                      () => _termsAccepted = v ?? false),
                                  activeColor: AppColors.primary,
                                  shape: RoundedRectangleBorder(
                                    borderRadius: BorderRadius.circular(4),
                                  ),
                                ),
                              ),
                              const SizedBox(width: 10),
                              Expanded(
                                child: Padding(
                                  padding: const EdgeInsets.only(top: 2),
                                  child: Wrap(
                                    children: [
                                      const Text(
                                        'I agree to the ',
                                        style: TextStyle(
                                          color: Colors.black87,
                                          fontSize: 13,
                                          height: 1.4,
                                        ),
                                      ),
                                      GestureDetector(
                                        onTap: _showTermsAndConditions,
                                        child: const Text(
                                          'Terms and Conditions',
                                          style: TextStyle(
                                            color: AppColors.primary,
                                            fontSize: 13,
                                            fontWeight: FontWeight.w700,
                                            decoration:
                                                TextDecoration.underline,
                                            decorationColor: AppColors.primary,
                                            height: 1.4,
                                          ),
                                        ),
                                      ),
                                      const Text(
                                        '.',
                                        style: TextStyle(
                                          color: Colors.black87,
                                          fontSize: 13,
                                          height: 1.4,
                                        ),
                                      ),
                                    ],
                                  ),
                                ),
                              ),
                            ],
                          ),
                          const SizedBox(height: 20),

                          // Create Account button
                          PrimaryButton(
                            label: 'Create Account',
                            backgroundColor: AppColors.primary,
                            isLoading: _isLoading,
                            onPressed: _handleRegister,
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

  void _clearFieldError(String key) {
    if (_fieldErrors.containsKey(key)) {
      setState(() => _fieldErrors.remove(key));
    }
  }

  Future<void> _showTermsAndConditions() {
    _termsDialogAgreed = _termsAccepted;

    return showDialog<void>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setDialogState) {
            return Dialog(
              insetPadding:
                  const EdgeInsets.symmetric(horizontal: 20, vertical: 24),
              shape: RoundedRectangleBorder(
                borderRadius: BorderRadius.circular(20),
              ),
              child: ConstrainedBox(
                constraints:
                    const BoxConstraints(maxWidth: 560, maxHeight: 700),
                child: Padding(
                  padding: const EdgeInsets.all(20),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          const Expanded(
                            child: Text(
                              'Terms and Conditions',
                              style: TextStyle(
                                fontSize: 20,
                                fontWeight: FontWeight.w800,
                                color: AppColors.textDark,
                              ),
                            ),
                          ),
                          IconButton(
                            onPressed: () => Navigator.of(context).pop(),
                            icon: const Icon(Icons.close),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      const Text(
                        'Please read the full agreement before creating your TechQuiz account.',
                        style: TextStyle(
                          fontSize: 13,
                          color: AppColors.gray600,
                          height: 1.5,
                        ),
                      ),
                      const SizedBox(height: 16),
                      Expanded(
                        child: Container(
                          width: double.infinity,
                          padding: const EdgeInsets.all(16),
                          decoration: BoxDecoration(
                            color: AppColors.gray100,
                            borderRadius: BorderRadius.circular(14),
                            border: Border.all(color: AppColors.gray200),
                          ),
                          child: const SingleChildScrollView(
                            child: Column(
                              crossAxisAlignment: CrossAxisAlignment.start,
                              children: [
                                _TermsSection(
                                  title: '1. Acceptance of Terms',
                                  body:
                                      'By registering for TechQuiz, you acknowledge that you have read, understood, and agreed to these Terms and Conditions. If you do not agree, you must not create an account or use the system.',
                                ),
                                _TermsSection(
                                  title: '2. Educational Use Only',
                                  body:
                                      'TechQuiz is provided for academic and learning-related activities. Your account, quiz history, scores, and records are intended to support participation in school-related assessments and progress tracking.',
                                ),
                                _TermsSection(
                                  title: '3. Accurate Registration Information',
                                  body:
                                      'You agree to provide complete and accurate information during registration, including your student details, section, and school email. False or misleading information may lead to account restrictions or removal.',
                                ),
                                _TermsSection(
                                  title: '4. Account Responsibility',
                                  body:
                                      'You are responsible for maintaining the confidentiality of your login credentials. Any activity performed using your account will be treated as your own unless reported through proper channels.',
                                ),
                                _TermsSection(
                                  title: '5. Proper Platform Conduct',
                                  body:
                                      'You must not misuse the application, disrupt quizzes, attempt unauthorized access, manipulate scores, or interfere with the experience of other users, instructors, or administrators.',
                                ),
                                _TermsSection(
                                  title: '6. Quiz Attempts and Records',
                                  body:
                                      'The system stores quiz attempts, results, completion times, and related academic activity to provide performance tracking, history viewing, analytics, and reporting features for authorized educational purposes.',
                                ),
                                _TermsSection(
                                  title: '7. Data and Privacy Awareness',
                                  body:
                                      'Your registration details and quiz activity may be processed within the system to manage your account, identify your academic records, and support the features required by the platform.',
                                ),
                                _TermsSection(
                                  title: '8. Service Updates and Availability',
                                  body:
                                      'TechQuiz may be updated, improved, or temporarily unavailable because of maintenance, bug fixes, feature releases, or school administration needs. Reasonable effort will be made to keep the service accessible.',
                                ),
                                _TermsSection(
                                  title: '9. Limitation of Use',
                                  body:
                                      'You agree to use the application only for its intended educational purpose and in accordance with school rules, classroom expectations, and any instructions provided by your instructors or administrators.',
                                ),
                                _TermsSection(
                                  title: '10. Final Agreement',
                                  body:
                                      'Selecting the agreement option confirms that you voluntarily accept these Terms and Conditions and understand that registration cannot continue unless this agreement is provided.',
                                ),
                              ],
                            ),
                          ),
                        ),
                      ),
                      const SizedBox(height: 16),
                      Container(
                        padding: const EdgeInsets.symmetric(
                          horizontal: 12,
                          vertical: 10,
                        ),
                        decoration: BoxDecoration(
                          color: Colors.white,
                          borderRadius: BorderRadius.circular(12),
                          border: Border.all(color: AppColors.gray200),
                        ),
                        child: Row(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Checkbox(
                              value: _termsDialogAgreed,
                              onChanged: (value) {
                                setDialogState(() {
                                  _termsDialogAgreed = value ?? false;
                                });
                              },
                              activeColor: AppColors.primary,
                            ),
                            Expanded(
                              child: GestureDetector(
                                onTap: () {
                                  setDialogState(() {
                                    _termsDialogAgreed = !_termsDialogAgreed;
                                  });
                                },
                                child: const Padding(
                                  padding: EdgeInsets.only(top: 12),
                                  child: Text(
                                    'I have read and agree to the Terms and Conditions.',
                                    style: TextStyle(
                                      fontSize: 13,
                                      height: 1.4,
                                      color: AppColors.textDark,
                                    ),
                                  ),
                                ),
                              ),
                            ),
                          ],
                        ),
                      ),
                      const SizedBox(height: 16),
                      SizedBox(
                        width: double.infinity,
                        child: ElevatedButton(
                          onPressed: _termsDialogAgreed
                              ? () {
                                  setState(() => _termsAccepted = true);
                                  Navigator.of(context).pop();
                                }
                              : null,
                          style: ElevatedButton.styleFrom(
                            backgroundColor: AppColors.primary,
                            foregroundColor: Colors.white,
                            disabledBackgroundColor: AppColors.gray400,
                            disabledForegroundColor: Colors.white,
                            padding: const EdgeInsets.symmetric(vertical: 14),
                            shape: RoundedRectangleBorder(
                              borderRadius: BorderRadius.circular(12),
                            ),
                          ),
                          child: const Text('Agree and Continue'),
                        ),
                      ),
                    ],
                  ),
                ),
              ),
            );
          },
        );
      },
    );
  }

  Widget _buildField({
    required TextEditingController controller,
    required String hint,
    TextInputType keyboardType = TextInputType.text,
    bool obscureText = false,
    String? Function(String?)? validator,
    Widget? suffixIcon,
    String? errorText,
    ValueChanged<String>? onChanged,
  }) {
    return AppTextField(
      controller: controller,
      hint: hint,
      keyboardType: keyboardType,
      obscureText: obscureText,
      validator: validator,
      suffixIcon: suffixIcon,
      errorText: errorText,
      onChanged: onChanged,
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
        color: isError ? AppColors.dangerBg : Colors.green.shade50,
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

class _TermsSection extends StatelessWidget {
  final String title;
  final String body;

  const _TermsSection({required this.title, required this.body});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 14),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w700,
              color: AppColors.textDark,
            ),
          ),
          const SizedBox(height: 4),
          Text(
            body,
            style: const TextStyle(
              fontSize: 13,
              height: 1.5,
              color: AppColors.gray600,
            ),
          ),
        ],
      ),
    );
  }
}
