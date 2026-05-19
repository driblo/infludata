import { Image, StyleSheet, Text, View } from 'react-native';

export function Avatar({ uri, fallback, size = 40 }: { uri?: string | null; fallback: string; size?: number }) {
  if (uri) {
    return (
      <Image
        source={{ uri }}
        style={[styles.base, { width: size, height: size, borderRadius: size / 2 }]}
        accessibilityIgnoresInvertColors
      />
    );
  }
  return (
    <View style={[styles.base, styles.fallback, { width: size, height: size, borderRadius: size / 2 }]}>
      <Text style={styles.text}>{fallback.charAt(0).toUpperCase()}</Text>
    </View>
  );
}

const styles = StyleSheet.create({
  base: { backgroundColor: '#1A1D40' },
  fallback: { alignItems: 'center', justifyContent: 'center' },
  text: { color: '#fff', fontWeight: '700' },
});
