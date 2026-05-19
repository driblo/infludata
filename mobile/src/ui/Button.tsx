import { ActivityIndicator, Pressable, StyleSheet, Text, type PressableProps } from 'react-native';

type Variant = 'primary' | 'outlined' | 'danger';

type Props = Omit<PressableProps, 'children'> & {
  label: string;
  loading?: boolean;
  variant?: Variant;
};

export function Button({ label, loading = false, variant = 'primary', disabled, ...rest }: Props) {
  const palette = stylesByVariant[variant];
  return (
    <Pressable
      accessibilityRole="button"
      style={({ pressed }) => [
        styles.base,
        palette.container,
        (disabled || loading) && styles.disabled,
        pressed && !disabled && styles.pressed,
      ]}
      disabled={disabled || loading}
      {...rest}
    >
      {loading ? (
        <ActivityIndicator color={palette.text.color} />
      ) : (
        <Text style={[styles.label, palette.text]}>{label}</Text>
      )}
    </Pressable>
  );
}

const styles = StyleSheet.create({
  base: {
    minHeight: 44,
    paddingHorizontal: 16,
    paddingVertical: 12,
    borderRadius: 10,
    alignItems: 'center',
    justifyContent: 'center',
  },
  label: { fontWeight: '600', fontSize: 15 },
  disabled: { opacity: 0.5 },
  pressed: { opacity: 0.85 },
});

const stylesByVariant: Record<Variant, { container: object; text: { color: string } }> = {
  primary: { container: { backgroundColor: '#7B61FF' }, text: { color: '#fff' } },
  outlined: { container: { borderWidth: 1, borderColor: '#7B61FF' }, text: { color: '#7B61FF' } },
  danger: { container: { backgroundColor: '#D62F4E' }, text: { color: '#fff' } },
};
