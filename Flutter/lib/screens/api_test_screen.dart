import 'package:flutter/material.dart';
import '../services/ping_service.dart';
import '../core/exceptions/api_exception.dart';
import '../core/config/api_config.dart';

/// Test screen to verify API connectivity
class ApiTestScreen extends StatefulWidget {
  const ApiTestScreen({super.key});

  @override
  State<ApiTestScreen> createState() => _ApiTestScreenState();
}

class _ApiTestScreenState extends State<ApiTestScreen> {
  final PingService _pingService = PingService();
  String _response = 'No test run yet';
  bool _isLoading = false;
  bool _success = false;

  Future<void> _testConnection() async {
    setState(() {
      _isLoading = true;
      _response = 'Testing connection...';
      _success = false;
    });

    try {
      final result = await _pingService.testConnection();
      setState(() {
        _response = 'Response: ${result.toString()}';
        _success = true;
      });
    } on ApiException catch (e) {
      setState(() {
        _response = 'Error: ${e.toString()}';
        _success = false;
      });
    } catch (e) {
      setState(() {
        _response = 'Unexpected error: $e';
        _success = false;
      });
    } finally {
      setState(() {
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('API Connection Test'),
      ),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text(
                      'API Status',
                      style: TextStyle(
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 12),
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: _success
                            ? Colors.green.shade50
                            : Colors.grey.shade100,
                        border: Border.all(
                          color: _success ? Colors.green : Colors.grey,
                        ),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            _success ? '✓ Connected' : '✗ Not tested',
                            style: TextStyle(
                              color: _success
                                  ? Colors.green
                                  : Colors.grey.shade600,
                              fontWeight: FontWeight.bold,
                            ),
                          ),
                          const SizedBox(height: 8),
                          Text(
                            _response,
                            style: TextStyle(
                              fontSize: 12,
                              color: Colors.grey.shade700,
                              fontFamily: 'monospace',
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _isLoading ? null : _testConnection,
              icon: _isLoading
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(
                        strokeWidth: 2,
                      ),
                    )
                  : const Icon(Icons.network_check),
              label: Text(_isLoading ? 'Testing...' : 'Test Connection'),
            ),
            const SizedBox(height: 32),
            const Text(
              'Endpoint Information',
              style: TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 12),
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: Colors.blue.shade50,
                border: Border.all(color: Colors.blue),
                borderRadius: BorderRadius.circular(8),
              ),
              child: const Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('Base URL: ${ApiConfig.baseUrl}'),
                  SizedBox(height: 8),
                  Text('Endpoint: /test'),
                  SizedBox(height: 8),
                  Text('Method: GET'),
                  SizedBox(height: 8),
                  Text('Expected: {message: "API route is working!"}'),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}
