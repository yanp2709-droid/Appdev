import 'dart:convert';
import 'dart:html' as html;

import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:just_audio/just_audio.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'services/api_service.dart';

void main() {
  runApp(const EmoTuneApp());
}

class EmoTuneApp extends StatefulWidget {
  const EmoTuneApp({super.key});

  @override
  State<EmoTuneApp> createState() => _EmoTuneAppState();
}

class _EmoTuneAppState extends State<EmoTuneApp> {
  final ApiService api = ApiService();
  bool darkMode = true;

  @override
  void initState() {
    super.initState();
    _loadTheme();
    _consumeAuthPayload();
  }

  Future<void> _loadTheme() async {
    final prefs = await SharedPreferences.getInstance();
    setState(() => darkMode = prefs.getBool('darkMode') ?? true);
  }

  Future<void> _consumeAuthPayload() async {
    final fragment = Uri.base.fragment;
    if (!fragment.contains('payload=')) return;
    final query = fragment.split('?').length > 1 ? fragment.split('?')[1] : '';
    final payload = Uri.splitQueryString(query)['payload'];
    if (payload == null) return;

    final decoded = jsonDecode(payload) as Map<String, dynamic>;
    api.jwt = decoded['access'] as String?;

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString('jwt', api.jwt ?? '');
  }

  Future<void> _toggleTheme() async {
    setState(() => darkMode = !darkMode);
    final prefs = await SharedPreferences.getInstance();
    await prefs.setBool('darkMode', darkMode);
  }

  @override
  Widget build(BuildContext context) {
    return MaterialApp(
      debugShowCheckedModeBanner: false,
      title: 'EmoTune',
      themeMode: darkMode ? ThemeMode.dark : ThemeMode.light,
      darkTheme: ThemeData.dark().copyWith(
        scaffoldBackgroundColor: Colors.black,
        colorScheme: const ColorScheme.dark(primary: Color(0xFFB6F05A)),
      ),
      theme: ThemeData.light().copyWith(
        colorScheme: ColorScheme.fromSeed(seedColor: const Color(0xFF58D6E8)),
      ),
      home: MainShell(api: api, darkMode: darkMode, onToggleTheme: _toggleTheme),
    );
  }
}

class MainShell extends StatefulWidget {
  const MainShell({super.key, required this.api, required this.darkMode, required this.onToggleTheme});

  final ApiService api;
  final bool darkMode;
  final Future<void> Function() onToggleTheme;

  @override
  State<MainShell> createState() => _MainShellState();
}

class _MainShellState extends State<MainShell> {
  int index = 0;
  final AudioPlayer player = AudioPlayer();
  Map<String, dynamic>? nowPlaying;

  @override
  void dispose() {
    player.dispose();
    super.dispose();
  }

  Future<void> loginWithSpotify() async {
    final url = await widget.api.spotifyAuthUrl();
    html.window.location.href = url;
  }

  Future<void> playTrack(Map<String, dynamic> track, String mood) async {
    final preview = (track['preview_url'] ?? '') as String;
    if (preview.isEmpty) return;
    await player.setUrl(preview);
    await player.play();
    setState(() => nowPlaying = track);
    await widget.api.playEvent({
      'track_id': track['id'],
      'track_name': track['name'],
      'played_seconds': 30,
      'mood': mood,
    });
  }

  @override
  Widget build(BuildContext context) {
    final pages = [
      HomeScreen(api: widget.api, onLogin: loginWithSpotify),
      FavoriteScreen(api: widget.api, onPlay: (track) => playTrack(track, 'mixed')),
      RecommendationScreen(api: widget.api, onPlay: playTrack),
      HistoryScreen(api: widget.api),
      ProfileScreen(api: widget.api, darkMode: widget.darkMode, onToggleTheme: widget.onToggleTheme),
    ];

    return Scaffold(
      body: SafeArea(child: pages[index]),
      bottomNavigationBar: NavigationBar(
        selectedIndex: index,
        onDestinationSelected: (value) => setState(() => index = value),
        destinations: const [
          NavigationDestination(icon: Icon(Icons.home_outlined), label: 'Home'),
          NavigationDestination(icon: Icon(Icons.favorite_border), label: 'Favorite'),
          NavigationDestination(icon: Icon(Icons.music_note_outlined), label: 'Recommendation'),
          NavigationDestination(icon: Icon(Icons.history), label: 'History'),
          NavigationDestination(icon: Icon(Icons.person_outline), label: 'Profile'),
        ],
      ),
    );
  }
}

class HomeScreen extends StatelessWidget {
  const HomeScreen({super.key, required this.api, required this.onLogin});

  final ApiService api;
  final Future<void> Function() onLogin;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 20),
          const Text('Welcome to EmoTune', style: TextStyle(fontSize: 28, fontWeight: FontWeight.bold)),
          const SizedBox(height: 10),
          const Text('Let your mood decide your music.'),
          const SizedBox(height: 24),
          ElevatedButton.icon(
            onPressed: onLogin,
            icon: const Icon(Icons.login),
            label: const Text('Login with Spotify'),
          ),
          const SizedBox(height: 16),
          const Text('Features: AI mood playlist, adaptive song ordering, in-app play/pause, favorites, history.'),
        ],
      ),
    );
  }
}

class RecommendationScreen extends StatefulWidget {
  const RecommendationScreen({super.key, required this.api, required this.onPlay});

  final ApiService api;
  final Future<void> Function(Map<String, dynamic> track, String mood) onPlay;

  @override
  State<RecommendationScreen> createState() => _RecommendationScreenState();
}

class _RecommendationScreenState extends State<RecommendationScreen> {
  final controller = TextEditingController();
  String aiMessage = '';
  String mood = 'mixed';
  List<dynamic> tracks = [];

  Future<void> submitPrompt() async {
    final data = await widget.api.sendPrompt(controller.text);
    setState(() {
      aiMessage = data['ai_message'] as String? ?? '';
      mood = data['emotion'] as String? ?? 'mixed';
      tracks = data['tracks'] as List<dynamic>? ?? [];
    });
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        children: [
          TextField(
            controller: controller,
            decoration: const InputDecoration(
              labelText: 'How do you feel right now?',
              border: OutlineInputBorder(),
            ),
            minLines: 2,
            maxLines: 4,
          ),
          const SizedBox(height: 12),
          ElevatedButton(onPressed: submitPrompt, child: const Text('Get Playlist')),
          const SizedBox(height: 10),
          if (aiMessage.isNotEmpty) Text(aiMessage),
          const SizedBox(height: 10),
          Expanded(
            child: ListView.builder(
              itemCount: tracks.length,
              itemBuilder: (_, i) {
                final t = tracks[i] as Map<String, dynamic>;
                return Card(
                  child: ListTile(
                    title: Text('${t['name']}'),
                    subtitle: Text('${t['artist']} | mood: $mood'),
                    trailing: IconButton(
                      icon: const Icon(Icons.play_arrow),
                      onPressed: () => widget.onPlay(t, mood),
                    ),
                  ),
                );
              },
            ),
          ),
        ],
      ),
    );
  }
}

class FavoriteScreen extends StatefulWidget {
  const FavoriteScreen({super.key, required this.api, required this.onPlay});

  final ApiService api;
  final Future<void> Function(Map<String, dynamic>) onPlay;

  @override
  State<FavoriteScreen> createState() => _FavoriteScreenState();
}

class _FavoriteScreenState extends State<FavoriteScreen> {
  List<dynamic> items = [];

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final data = await widget.api.favorites();
    setState(() => items = data);
  }

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: items.length,
      itemBuilder: (_, i) {
        final t = items[i] as Map<String, dynamic>;
        return Card(
          child: ListTile(
            title: Text('${t['track_name']}'),
            subtitle: Text('${t['artist_name']}'),
            trailing: IconButton(
              icon: const Icon(Icons.play_arrow),
              onPressed: () => widget.onPlay({
                'id': t['track_id'],
                'name': t['track_name'],
                'artist': t['artist_name'],
                'preview_url': t['preview_url']
              }),
            ),
          ),
        );
      },
    );
  }
}

class HistoryScreen extends StatefulWidget {
  const HistoryScreen({super.key, required this.api});

  final ApiService api;

  @override
  State<HistoryScreen> createState() => _HistoryScreenState();
}

class _HistoryScreenState extends State<HistoryScreen> {
  List<dynamic> history = [];

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final data = await widget.api.history();
    setState(() => history = data);
  }

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: history.length,
      itemBuilder: (_, i) {
        final h = history[i] as Map<String, dynamic>;
        return Card(
          child: ListTile(
            title: Text('${h['predicted_mood']} | ${h['playlist_name']}'),
            subtitle: Text('${h['prompt']}'),
          ),
        );
      },
    );
  }
}

class ProfileScreen extends StatefulWidget {
  const ProfileScreen({super.key, required this.api, required this.darkMode, required this.onToggleTheme});

  final ApiService api;
  final bool darkMode;
  final Future<void> Function() onToggleTheme;

  @override
  State<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends State<ProfileScreen> {
  Map<String, dynamic> profile = {};

  @override
  void initState() {
    super.initState();
    load();
  }

  Future<void> load() async {
    final data = await widget.api.profile();
    setState(() => profile = data);
  }

  @override
  Widget build(BuildContext context) {
    final moods = (profile['mood_distribution'] as List<dynamic>? ?? []);
    final chart = moods.isEmpty
        ? const Text('No mood data yet.')
        : PieChart(
            PieChartData(
              sections: moods
                  .map(
                    (e) => PieChartSectionData(
                      value: (e['total'] as num).toDouble(),
                      title: e['predicted_mood'] as String,
                    ),
                  )
                  .toList(),
            ),
          );

    return Padding(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('Username: ${profile['username'] ?? '-'}'),
          Text('Email: ${profile['email'] ?? '-'}'),
          const SizedBox(height: 12),
          Row(
            children: [
              const Text('Dark mode'),
              Switch(value: widget.darkMode, onChanged: (_) => widget.onToggleTheme()),
            ],
          ),
          const SizedBox(height: 16),
          const Text('Your prompts by emotion'),
          const SizedBox(height: 12),
          SizedBox(height: 220, child: chart),
        ],
      ),
    );
  }
}
