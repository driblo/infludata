import { StyleSheet, Text, View } from 'react-native';

export function ErrorState({ message }: { message: string }) {
  return (
    <View style={styles.wrap}>
      <Text style={styles.title}>Something went wrong</Text>
      <Text style={styles.body}>{message}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
  title: { color: '#D62F4E', fontWeight: '700', marginBottom: 4 },
  body: { color: '#C8CDE8', textAlign: 'center' },
});
