import 'package:flutter/material.dart';

import '../../../core/constants/app_colors.dart';
import '../../auth/providers/auth_provider.dart';
import 'package:go_router/go_router.dart';
import 'package:provider/provider.dart';
import '../../../core/network/api_client.dart';
import 'dart:convert';

class TeacherProfileScreen extends StatelessWidget {
  const TeacherProfileScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      backgroundColor: AppColors.gray100,
      appBar: AppBar(
        title: const Text('Teacher Profile'),
        leading: IconButton(
          icon: const Icon(Icons.arrow_back_rounded),
          onPressed: () => Navigator.of(context).maybePop(),
        ),
      ),
      body: SafeArea(
        child: ListView(
          padding: const EdgeInsets.all(20),
          children: [
            _TeacherHeader(
              teacherName: 'Mr. Alex Johnson',
              subject: 'IT / Computer Science',
              tagline: 'Building problem-solvers with practical programming.',
            ),
            const SizedBox(height: 16),
            _InfoCard(
              title: 'About',
              lines: [
                'Focused on real-world software development concepts.',
                'Teaches fundamentals to advanced topics with hands-on quizzes.',
                'Encourages students to think logically and code confidently.',
              ],
            ),
            const SizedBox(height: 16),
            _TwoColumnInfo(
              leftTitle: 'Experience',
              leftValue: '8+ years',
              leftIcon: Icons.school_rounded,
              rightTitle: 'Specialty',
              rightValue: 'Programming & Algorithms',
              rightIcon: Icons.memory_rounded,
            ),
            const SizedBox(height: 16),
            _InfoCard(
              title: 'Courses / Topics',
              lines: [
                'Data Structures (Arrays, Trees, Graphs)',
                'Algorithms & Complexity',
                'Web Development Basics',
                'Debugging & Code Quality',
              ],
            ),
            const SizedBox(height: 16),
            _InfoCard(
              title: 'Certifications',
              lines: [
                'Cloud Fundamentals (IT Track)',
                'Secure Coding Essentials',
                'Database Design & Normalization',
              ],
            ),
            const SizedBox(height: 24),
            _PrimaryButton(
              label: 'Back',
              onPressed: () => Navigator.of(context).maybePop(),
            ),
          ],
        ),
      ),
    );
  }
}

class _TeacherHeader extends StatelessWidget {
  final String teacherName;
  final String subject;
  final String tagline;

  const _TeacherHeader({
    required this.teacherName,
    required this.subject,
    required this.tagline,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        boxShadow: [
          BoxShadow(
            color: Colors.black.withValues(alpha: 0.04),
            blurRadius: 16,
            offset: const Offset(0, 6),
          ),
        ],
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          CircleAvatar(
            radius: 34,
            backgroundColor: AppColors.primary.withValues(alpha: 0.12),
            child: Icon(
              Icons.person_rounded,
              color: AppColors.primary,
              size: 36,
            ),
          ),
          const SizedBox(width: 14),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  teacherName,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.w900,
                    color: AppColors.textDark,
                  ),
                ),
                const SizedBox(height: 6),
                Text(
                  subject,
                  style: TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w700,
                    color: AppColors.primary,
                  ),
                ),
                const SizedBox(height: 10),
                Text(
                  tagline,
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppColors.gray600,
                    height: 1.4,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoCard extends StatelessWidget {
  final String title;
  final List<String> lines;

  const _InfoCard({required this.title, required this.lines});

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: const TextStyle(
              fontSize: 16,
              fontWeight: FontWeight.w900,
              color: AppColors.textDark,
            ),
          ),
          const SizedBox(height: 12),
          ...lines
              .map(
                (e) => Padding(
                  padding: const EdgeInsets.only(bottom: 8),
                  child: Text(
                    e,
                    style: const TextStyle(
                      fontSize: 14,
                      color: AppColors.gray600,
                      height: 1.4,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
              )
              .toList(),
        ],
      ),
    );
  }
}

class _TwoColumnInfo extends StatelessWidget {
  final String leftTitle;
  final String leftValue;
  final IconData leftIcon;
  final String rightTitle;
  final String rightValue;
  final IconData rightIcon;

  const _TwoColumnInfo({
    required this.leftTitle,
    required this.leftValue,
    required this.leftIcon,
    required this.rightTitle,
    required this.rightValue,
    required this.rightIcon,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _MiniStatCard(
            icon: leftIcon,
            title: leftTitle,
            value: leftValue,
          ),
        ),
        const SizedBox(width: 14),
        Expanded(
          child: _MiniStatCard(
            icon: rightIcon,
            title: rightTitle,
            value: rightValue,
          ),
        ),
      ],
    );
  }
}

class _MiniStatCard extends StatelessWidget {
  final IconData icon;
  final String title;
  final String value;

  const _MiniStatCard({
    required this.icon,
    required this.title,
    required this.value,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(18),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(20),
        border: Border.all(color: AppColors.gray200),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            icon,
            color: AppColors.primary,
            size: 26,
          ),
          const SizedBox(height: 10),
          Text(
            title,
            style: TextStyle(
              fontSize: 13,
              color: AppColors.gray600,
              fontWeight: FontWeight.w700,
            ),
          ),
          const SizedBox(height: 8),
          Text(
            value,
            style: const TextStyle(
              fontSize: 18,
              color: AppColors.textDark,
              fontWeight: FontWeight.w900,
            ),
          ),
        ],
      ),
    );
  }
}

class _PrimaryButton extends StatelessWidget {
  final String label;
  final VoidCallback onPressed;

  const _PrimaryButton({required this.label, required this.onPressed});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      height: 52,
      child: ElevatedButton(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.primary,
          foregroundColor: Colors.white,
          elevation: 0,
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(16),
          ),
        ),
        onPressed: onPressed,
        child: Text(
          label,
          style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w800),
        ),
      ),
    );
  }
}

