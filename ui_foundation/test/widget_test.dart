import 'package:flutter_test/flutter_test.dart';
import 'package:ui_foundation/main.dart'; // make sure this matches your project name

void main() {
  testWidgets('Login screen displays correctly', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const TechQuizApp()); // <-- use your app class

    // Verify that the Login screen is displayed.
    expect(find.text('Login Screen'), findsOneWidget);

    // Verify the login buttons exist.
    expect(find.text('Login'), findsOneWidget);
  });
}