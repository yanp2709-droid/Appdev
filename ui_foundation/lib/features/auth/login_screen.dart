import 'package:flutter/material.dart';
import '../../app/widgets/primary_button.dart';
import '../../app/widgets/app_text_field.dart';

class LoginScreen extends StatefulWidget {
  const LoginScreen({super.key});

  @override
  State<LoginScreen> createState() => _LoginScreenState();
}

class _LoginScreenState extends State<LoginScreen> {
  final TextEditingController emailController = TextEditingController();
  final TextEditingController passwordController = TextEditingController();
  String? emailError;
  String? passwordError;

  void _login() {
    setState(() {
      emailError = emailController.text.isEmpty ? "Email is required" : null;
      passwordError = passwordController.text.isEmpty ? "Password is required" : null;
    });

    if (emailError == null && passwordError == null) {
      // Perform login (for now, just print)
      print("Login with ${emailController.text}");
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text("Login")),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            AppTextField(
              label: "Email",
              controller: emailController,
              errorText: emailError,
            ),
            const SizedBox(height: 16),
            AppTextField(
              label: "Password",
              controller: passwordController,
              obscureText: true,
              errorText: passwordError,
            ),
            const SizedBox(height: 24),
            PrimaryButton(
              text: "Login",
              onPressed: _login,
            ),
          ],
        ),
      ),
    );
  }
}