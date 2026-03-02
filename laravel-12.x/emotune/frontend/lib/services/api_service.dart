import 'dart:convert';

import 'package:http/http.dart' as http;

import '../config.dart';

class ApiService {
  String? jwt;

  Map<String, String> get _headers => {
        'Content-Type': 'application/json',
        if (jwt != null) 'Authorization': 'Bearer $jwt',
      };

  Future<String> spotifyAuthUrl() async {
    final response = await http.get(Uri.parse('$apiBase/auth/spotify/login/'));
    final data = jsonDecode(response.body) as Map<String, dynamic>;
    return data['auth_url'] as String;
  }

  Future<Map<String, dynamic>> sendPrompt(String prompt) async {
    final response = await http.post(
      Uri.parse('$apiBase/recommendations/'),
      headers: _headers,
      body: jsonEncode({'prompt': prompt}),
    );
    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<List<dynamic>> favorites() async {
    final response = await http.get(Uri.parse('$apiBase/favorites/'), headers: _headers);
    return jsonDecode(response.body) as List<dynamic>;
  }

  Future<void> addFavorite(Map<String, dynamic> track) async {
    await http.post(
      Uri.parse('$apiBase/favorites/'),
      headers: _headers,
      body: jsonEncode({
        'track_id': track['id'],
        'track_name': track['name'],
        'artist_name': track['artist'],
        'preview_url': track['preview_url'] ?? '',
        'image_url': track['image'] ?? '',
      }),
    );
  }

  Future<List<dynamic>> history() async {
    final response = await http.get(Uri.parse('$apiBase/history/'), headers: _headers);
    return jsonDecode(response.body) as List<dynamic>;
  }

  Future<Map<String, dynamic>> profile() async {
    final response = await http.get(Uri.parse('$apiBase/profile/'), headers: _headers);
    return jsonDecode(response.body) as Map<String, dynamic>;
  }

  Future<void> playEvent(Map<String, dynamic> payload) async {
    await http.post(
      Uri.parse('$apiBase/play-events/'),
      headers: _headers,
      body: jsonEncode(payload),
    );
  }
}
