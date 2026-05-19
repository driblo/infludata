import { StyleSheet, View } from 'react-native';
import { Text } from 'react-native-paper';

export function EmptyState({ title, hint }: { title: string; hint?: string }) {
  return (
    <View style={styles.wrap}>
      <Text variant="titleMedium" style={styles.title}>
        {title}
      </Text>
      {hint ? (
        <Text variant="bodyMedium" style={styles.hint}>
          {hint}
        </Text>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
  title: { marginBottom: 6 },
  hint: { textAlign: 'center', opacity: 0.7 },
});
