import 'package:dio/dio.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import '../../core/api/api_client.dart';

class ContentItem {
  const ContentItem({
    required this.id,
    required this.kind,
    this.title,
    this.url,
    this.thumbnailUrl,
    this.publishedAt,
  });

  factory ContentItem.fromJson(Map<String, dynamic> j) => ContentItem(
        id: j['id'] as int,
        kind: (j['kind'] as String?) ?? 'unknown',
        title: j['title'] as String?,
        url: j['url'] as String?,
        thumbnailUrl: j['thumbnail_url'] as String?,
        publishedAt: DateTime.tryParse((j['published_at'] as String?) ?? ''),
      );

  final int id;
  final String kind;
  final String? title;
  final String? url;
  final String? thumbnailUrl;
  final DateTime? publishedAt;
}

final creatorContentProvider =
    FutureProvider.family<List<ContentItem>, int>((ref, creatorId) async {
  final dio = ref.watch(apiClientProvider);
  try {
    final res = await dio.get<Map<String, dynamic>>('/api/creators/$creatorId/content');
    final data = (res.data?['data'] as List?) ?? const [];
    return data
        .whereType<Map<String, dynamic>>()
        .map(ContentItem.fromJson)
        .toList(growable: false);
  } on DioException {
    return const <ContentItem>[];
  }
});

class ContentListScreen extends ConsumerWidget {
  const ContentListScreen({super.key, required this.creatorId});

  final int creatorId;

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final list = ref.watch(creatorContentProvider(creatorId));
    final fmt = DateFormat.yMMMd();

    return Scaffold(
      appBar: AppBar(title: const Text('Content')),
      body: list.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (items) => items.isEmpty
            ? const Center(child: Text('No content fetched yet.'))
            : ListView.separated(
                itemCount: items.length,
                separatorBuilder: (_, __) => const Divider(height: 0),
                itemBuilder: (context, i) {
                  final it = items[i];
                  return ListTile(
                    leading: it.thumbnailUrl != null
                        ? AspectRatio(
                            aspectRatio: 1,
                            child: Image.network(it.thumbnailUrl!, fit: BoxFit.cover),
                          )
                        : const Icon(Icons.play_circle_outline),
                    title: Text(it.title ?? '(untitled ${it.kind})'),
                    subtitle: Text(
                      '${it.kind}'
                      '${it.publishedAt != null ? ' · ${fmt.format(it.publishedAt!)}' : ''}',
                    ),
                  );
                },
              ),
      ),
    );
  }
}
