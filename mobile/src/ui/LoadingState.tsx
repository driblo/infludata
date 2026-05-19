import { StyleSheet, View } from 'react-native';
import { ActivityIndicator } from 'react-native-paper';

export function LoadingState() {
  return (
    <View style={styles.wrap}>
      <ActivityIndicator />
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
});
