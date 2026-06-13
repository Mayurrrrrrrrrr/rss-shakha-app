import 'package:dio/dio.dart';
import 'package:shared_preferences/shared_preferences.dart';

class ApiClient {
  static const String baseUrl = 'https://sanghasthan.yuktaa.com';
  late final Dio _dio;

  ApiClient() {
    _dio = Dio(BaseOptions(
      baseUrl: baseUrl,
      connectTimeout: const Duration(seconds: 15),
      receiveTimeout: const Duration(seconds: 15),
    ));

    _dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final prefs = await SharedPreferences.getInstance();
          final token = prefs.getString('api_token');
          if (token != null && token.isNotEmpty) {
            options.headers['Authorization'] = 'Bearer $token';
            options.headers['X-API-Token'] = token;
            
            // Fallback for environments that strip custom headers
            final queryParams = Map<String, dynamic>.from(options.queryParameters);
            queryParams['token'] = token;
            options.queryParameters = queryParams;
          }
          return handler.next(options);
        },
        onError: (DioException e, handler) {
          // Handle global network or token errors here
          return handler.next(e);
        },
      ),
    );
  }

  Future<Response> get(String path, {Map<String, dynamic>? queryParameters}) async {
    try {
      return await _dio.get(path, queryParameters: queryParameters);
    } catch (e) {
      rethrow;
    }
  }

  Future<Response> post(String path, {dynamic data}) async {
    try {
      return await _dio.post(path, data: data);
    } catch (e) {
      rethrow;
    }
  }

  Future<Response> fetchPanchang(String date) async {
    try {
      return await get('/api/fetch_panchang.php', queryParameters: {'date': date});
    } catch (e) {
      rethrow;
    }
  }
}
