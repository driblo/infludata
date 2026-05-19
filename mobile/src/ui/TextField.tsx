import { StyleSheet, Text, TextInput, type TextInputProps, View } from 'react-native';

type Props = TextInputProps & {
  label: string;
  errorText?: string;
};

export function TextField({ label, errorText, style, ...rest }: Props) {
  return (
    <View style={styles.wrap}>
      <Text style={styles.label}>{label}</Text>
      <TextInput
        style={[styles.input, errorText ? styles.inputError : null, style]}
        placeholderTextColor="#7C82A1"
        {...rest}
      />
      {errorText ? <Text style={styles.err}>{errorText}</Text> : null}
    </View>
  );
}

const styles = StyleSheet.create({
  wrap: { marginBottom: 12 },
  label: { color: '#C8CDE8', marginBottom: 6, fontWeight: '500' },
  input: {
    borderWidth: 1,
    borderColor: '#2A2E55',
    backgroundColor: '#1A1D40',
    color: '#fff',
    paddingHorizontal: 12,
    paddingVertical: 10,
    borderRadius: 8,
    fontSize: 15,
  },
  inputError: { borderColor: '#D62F4E' },
  err: { color: '#D62F4E', marginTop: 4, fontSize: 12 },
});
