import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:techquiz/features/auth/data/auth_repository.dart';
import 'package:techquiz/features/auth/data/user_model.dart';
import 'package:techquiz/features/auth/providers/auth_provider.dart';
import 'package:techquiz/features/categories/data/categories_repository.dart';
import 'package:techquiz/features/categories/data/models/category.dart';
import 'package:techquiz/features/categories/providers/categories_provider.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();
  SharedPreferences.setMockInitialValues({});
  // ── UserModel ─────────────────────────────────────────────────────────────
  group('UserModel', () {
    test('fromMap creates correct model', () {
      final user = UserModel.fromMap({
        'name': 'Alex',
        'email': 'alex@student.com',
        'role': 'student',
        'latest_score': 85,
        'subjects_covered': 5,
      });
      expect(user.name,             'Alex');
      expect(user.email,            'alex@student.com');
      expect(user.role,             'student');
      expect(user.latestScore,      85);
      expect(user.subjectsCovered,  5);
    });

    test('toJson / fromJson round-trip', () {
      const original = UserModel(
        name: 'Alex', email: 'alex@student.com',
        role: 'student', latestScore: 85, subjectsCovered: 5,
      );
      final restored = UserModel.fromJson(original.toJson());
      expect(restored.name,            original.name);
      expect(restored.email,           original.email);
      expect(restored.role,            original.role);
      expect(restored.latestScore,     original.latestScore);
      expect(restored.subjectsCovered, original.subjectsCovered);
    });

    test('missing fields default gracefully', () {
      final user = UserModel.fromMap({'name': 'Bob'});
      expect(user.email,           '');
      expect(user.role,            'student');
      expect(user.latestScore,     0);
      expect(user.subjectsCovered, 0);
    });
  });

  // ── AuthProvider ──────────────────────────────────────────────────────────
  group('AuthProvider', () {
    late AuthProvider auth;

    setUp(() => auth = AuthProvider(AuthRepository()));

    test('starts with initial status', () {
      expect(auth.status,    AuthStatus.initial);
      expect(auth.isLoggedIn, false);
      expect(auth.user,      isNull);
      expect(auth.token,     isNull);
    });

    test('student login succeeds', () async {
      final ok = await auth.login('alex@student.com', 'password123');
      expect(ok,              true);
      expect(auth.isLoggedIn, true);
      expect(auth.user?.role, 'student');
      expect(auth.user?.name, 'Alex');
      expect(auth.token,      isNotNull);
    });

    test('wrong credentials fail', () async {
      final ok = await auth.login('bad@email.com', 'wrong');
      expect(ok,              false);
      expect(auth.isLoggedIn, false);
      expect(auth.status,     AuthStatus.error);
      expect(auth.errorMessage, isNotNull);
    });

    test('logout clears all state', () async {
      await auth.login('alex@student.com', 'password123');
      expect(auth.isLoggedIn, true);

      await auth.logout();
      expect(auth.isLoggedIn,   false);
      expect(auth.user,         isNull);
      expect(auth.token,        isNull);
      expect(auth.errorMessage, isNull);
      expect(auth.status,       AuthStatus.unauthenticated);
    });
  });

  // ── CategoriesProvider ────────────────────────────────────────────────────
  group('CategoriesProvider', () {
    late CategoriesProvider cats;

    setUp(() => cats = CategoriesProvider(CategoriesRepository()));

    test('starts with initial status', () {
      expect(cats.status,     CategoriesStatus.initial);
      expect(cats.categories, isEmpty);
    });

    test('fetch returns mock categories', () async {
      await cats.fetch();
      expect(cats.status,     CategoriesStatus.success);
      expect(cats.categories, isNotEmpty);
    });

    test('mock data contains Mathematics, Science, History', () async {
      await cats.fetch();
      final names = cats.categories.map((c) => c.name).toList();
      expect(names, containsAll(['Mathematics', 'Science', 'History']));
    });

    test('categories list is unmodifiable', () async {
      await cats.fetch();
      expect(
        () => (cats.categories as dynamic).add(
          const CategoryModel(id: 99, name: 'Test', description: 'Test'),
        ),
        throwsUnsupportedError,
      );
    });
  });
}
