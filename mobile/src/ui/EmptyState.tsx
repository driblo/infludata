import { StyleSheet, Text, View } from 'react-native';

export function EmptyState({ title, hint }: { title: string; hint?: string }) {
  return (
    <View style={styles.wrap}>
      <Text style={styles.title}>{title}</Text>
      {hint ? <Text style={styles.hint}>{hint}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
  title: { color: '#C8CDE8', fontWeight: '600', fontSize: 16, marginBottom: 6 },
  hint: { color: '#7C82A1', textAlign: 'center' },
});
