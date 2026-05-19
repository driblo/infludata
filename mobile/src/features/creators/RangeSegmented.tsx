import { Pressable, StyleSheet, Text, View } from 'react-native';

import { RANGES, type Range } from '@/api/types';

export function RangeSegmented({ value, onChange }: { value: Range; onChange: (r: Range) => void }) {
  return (
    <View style={styles.row}>
      {RANGES.map((r) => (
        <Pressable
          key={r}
          onPress={() => onChange(r)}
          style={[styles.btn, value === r && styles.active]}
          accessibilityRole="button"
        >
          <Text style={[styles.label, value === r && styles.labelActive]}>{r}</Text>
        </Pressable>
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  row: { flexDirection: 'row', backgroundColor: '#1A1D40', borderRadius: 8 },
  btn: { paddingVertical: 8, paddingHorizontal: 12, borderRadius: 8 },
  active: { backgroundColor: '#7B61FF' },
  label: { color: '#C8CDE8' },
  labelActive: { color: '#fff', fontWeight: '700' },
});
