import { StyleSheet, View } from 'react-native';
import { Text, useTheme } from 'react-native-paper';

export function ErrorState({ message }: { message: string }) {
  const theme = useTheme();
  return (
    <View style={styles.wrap}>
      <Text variant="titleSmall" style={{ color: theme.colors.error }}>
        Something went wrong
      </Text>
      <Text variant="bodyMedium" style={styles.body}>
        {message}
      </Text>
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { padding: 24, alignItems: 'center' },
  body: { textAlign: 'center', marginTop: 4 },
});
