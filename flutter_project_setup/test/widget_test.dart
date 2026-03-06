// This is a basic Flutter widget test.
//
// To perform an interaction with a widget in your test, use the WidgetTester
// utility in the flutter_test package. For example, you can send tap and scroll
// gestures. You can also use WidgetTester to find child widgets in the widget
// tree, read text, and verify that the values of widget properties are correct.

import 'package:flutter_test/flutter_test.dart';
import 'package:techquiz/main.dart';

void main() {
  testWidgets('App launches smoke test', (WidgetTester tester) async {
    // Build our app and trigger a frame.
    await tester.pumpWidget(const TechQuizApp());

    // Verify that the Login screen is displayed.
    expect(find.text('Login Screen'), findsOneWidget);

    // Optional: Verify navigation buttons exist.
    expect(find.text('Go to Student Home'), findsOneWidget);
    expect(find.text('Go to Admin Home'), findsOneWidget);
  });
}
