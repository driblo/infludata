import 'package:fl_chart/fl_chart.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';

import 'creators_controller.dart';
import 'models.dart';

class CreatorDetailScreen extends ConsumerStatefulWidget {
  const CreatorDetailScreen({super.key, required this.creatorId});

  final int creatorId;

  @override
  ConsumerState<CreatorDetailScreen> createState() => _CreatorDetailScreenState();
}

class _CreatorDetailScreenState extends ConsumerState<CreatorDetailScreen> {
  String _range = '30d';

  @override
  Widget build(BuildContext context) {
    final profile = ref.watch(creatorProfileProvider(widget.creatorId));
    final metrics = ref.watch(
      creatorMetricsProvider((id: widget.creatorId, range: _range)),
    );

    return Scaffold(
      appBar: AppBar(title: const Text('Creator')),
      body: profile.when(
        loading: () => const Center(child: CircularProgressIndicator()),
        error: (e, _) => Center(child: Text('Error: $e')),
        data: (p) => RefreshIndicator(
          onRefresh: () async {
            ref.invalidate(creatorProfileProvider(widget.creatorId));
            ref.invalidate(
              creatorMetricsProvider((id: widget.creatorId, range: _range)),
            );
          },
          child: ListView(
            padding: const EdgeInsets.all(16),
            children: [
              _Header(profile: p),
              const SizedBox(height: 24),
              Row(
                children: [
                  Text('Followers', style: Theme.of(context).textTheme.titleMedium),
                  const Spacer(),
                  SegmentedButton<String>(
                    showSelectedIcon: false,
                    segments: const [
                      ButtonSegment(value: '7d', label: Text('7d')),
                      ButtonSegment(value: '30d', label: Text('30d')),
                      ButtonSegment(value: '90d', label: Text('90d')),
                      ButtonSegment(value: '1y', label: Text('1y')),
                    ],
                    selected: {_range},
                    onSelectionChanged: (s) => setState(() => _range = s.first),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              SizedBox(
                height: 240,
                child: metrics.when(
                  loading: () => const Center(child: CircularProgressIndicator()),
                  error: (e, _) => Center(child: Text('Error: $e')),
                  data: (points) => _FollowersChart(points: points),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _Header extends StatelessWidget {
  const _Header({required this.profile});

  final CreatorProfile profile;

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        CircleAvatar(
          radius: 32,
          backgroundImage:
              profile.avatarUrl != null ? NetworkImage(profile.avatarUrl!) : null,
          child: profile.avatarUrl == null
              ? Text(profile.network[0].toUpperCase())
              : null,
        ),
        const SizedBox(width: 16),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(profile.displayName ?? profile.handle,
                  style: Theme.of(context).textTheme.titleLarge),
              Text('${profile.network} · @${profile.handle}'),
              const SizedBox(height: 4),
              Text(NumberFormat.compact().format(profile.followerCount) +
                  ' followers'),
            ],
          ),
        ),
      ],
    );
  }
}

class _FollowersChart extends StatelessWidget {
  const _FollowersChart({required this.points});

  final List<MetricPoint> points;

  @override
  Widget build(BuildContext context) {
    if (points.isEmpty) {
      return const Center(
        child: Text('No data yet — refresh after the next sync.'),
      );
    }

    final spots = <FlSpot>[];
    for (var i = 0; i < points.length; i++) {
      spots.add(FlSpot(i.toDouble(), points[i].followers.toDouble()));
    }

    return LineChart(
      LineChartData(
        gridData: const FlGridData(show: true, drawVerticalLine: false),
        titlesData: const FlTitlesData(
          rightTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
          topTitles: AxisTitles(sideTitles: SideTitles(showTitles: false)),
        ),
        lineBarsData: [
          LineChartBarData(
            spots: spots,
            isCurved: true,
            barWidth: 3,
            color: const Color(0xFF7B61FF),
            dotData: const FlDotData(show: false),
            belowBarData: BarAreaData(
              show: true,
              color: const Color(0xFF7B61FF).withValues(alpha: 0.15),
            ),
          ),
        ],
      ),
    );
  }
}
