import { ActivityIndicator, StyleSheet, View } from 'react-native';

export function LoadingState() {
  return (
    <View style={styles.wrap}>
      <ActivityIndicator color="#7B61FF" />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
});
