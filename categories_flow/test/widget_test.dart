<<<<<<< HEAD
import 'package:flutter_test/flutter_test.dart';
import 'package:categories_flow/main.dart';

void main() {
  testWidgets('App loads successfully', (WidgetTester tester) async {

    await tester.pumpWidget(const MyApp());

    expect(find.text('Quiz Categories'), findsOneWidget);

=======
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:auth_screen/main.dart'; // replace with your actual project name

void main() {
  testWidgets('Login screen displays correctly', (WidgetTester tester) async {
    // Build the app and trigger a frame.
    await tester.pumpWidget(const TechQuizApp()); // <-- use your app class

    // Check that Login screen is displayed.
    expect(find.text('Login'), findsOneWidget); // checks button
    expect(find.byType(TextField), findsNWidgets(2)); // email + password
>>>>>>> 6989a6f1071698ea32aaec745d984ba93999647b
  });
}