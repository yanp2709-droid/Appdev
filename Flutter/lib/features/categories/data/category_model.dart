import 'package:flutter/material.dart';

class CategoryModel {
  final int    id;
  final String name;
  final String subtitle;
  final Color  color;
  final String emoji;
  final int    questions;
  final int    minutes;
  final String? description;

  const CategoryModel({
    required this.id,
    required this.name,
    required this.subtitle,
    required this.color,
    required this.emoji,
    required this.questions,
    required this.minutes,
    this.description,
  });

  /// Create from API response (database)
  factory CategoryModel.fromJson(Map<String, dynamic> json) {
    final name = json['name'] as String? ?? '';
    final description = json['description'] as String? ?? '';
    
    return CategoryModel(
      id: json['id'] as int? ?? 0,
      name: name,
      description: description,
      subtitle: 'Categories · $description',
      color: _getColorForCategory(name),
      emoji: _getEmojiForCategory(name),
      questions: json['questions_count'] as int? ?? 0,
      minutes: json['time_limit'] as int? ?? 15,
    );
  }

  /// Legacy support - create from map with all fields
  factory CategoryModel.fromMap(Map<String, dynamic> map) {
    Color parsedColor = const Color(0xFF60A5FA);
    final hex = map['color'] as String?;
    if (hex != null) {
      try {
        parsedColor =
            Color(int.parse(hex.replaceFirst('#', '0xFF')));
      } catch (_) {}
    }
    return CategoryModel(
      id:        (map['id'] as int?) ?? 0,
      name:      (map['name'] as String?) ?? '',
      subtitle:  (map['subtitle'] as String?) ?? 'Categories · Interior categories',
      color:     parsedColor,
      emoji:     (map['emoji'] as String?) ?? '📚',
      questions: (map['questions'] as int?) ?? 10,
      minutes:   (map['minutes'] as int?) ?? 15,
    );
  }

  /// Get emoji based on category name
  static String _getEmojiForCategory(String categoryName) {
    final name = categoryName.toLowerCase();
    if (name.contains('math') || name.contains('number')) return '🔢';
    if (name.contains('science') || name.contains('physics') || name.contains('chemistry')) return '🔬';
    if (name.contains('history')) return '📜';
    if (name.contains('tech') || name.contains('computer') || name.contains('programming')) return '💻';
    if (name.contains('language') || name.contains('english')) return '📖';
    if (name.contains('art') || name.contains('design')) return '🎨';
    if (name.contains('music')) return '🎵';
    if (name.contains('sport') || name.contains('fitness')) return '⚽';
    return '📚'; // Default
  }

  /// Get color based on category name
  static Color _getColorForCategory(String categoryName) {
    final name = categoryName.toLowerCase();
    if (name.contains('math') || name.contains('number')) return const Color(0xFF60A5FA);
    if (name.contains('science')) return const Color(0xFFF87171);
    if (name.contains('history')) return const Color(0xFFFBBF24);
    if (name.contains('tech') || name.contains('computer')) return const Color(0xFF34D399);
    if (name.contains('language') || name.contains('english')) return const Color(0xFFA78BFA);
    if (name.contains('art') || name.contains('design')) return const Color(0xFFFB7185);
    return const Color(0xFF60A5FA); // Default blue
  }
}

