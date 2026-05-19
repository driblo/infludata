import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'expo-router';
import { Controller, useForm } from 'react-hook-form';
import { KeyboardAvoidingView, Platform, StyleSheet, View } from 'react-native';
import { Button, HelperText, Text, TextInput } from 'react-native-paper';
import { z } from 'zod';

import { authApi } from '@/api/endpoints/auth';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { Screen } from '@/ui/Screen';

const schema = z.object({
  email: z.string().email('Enter a valid email'),
  password: z.string().min(8, 'Min 8 characters'),
});

type FormValues = z.infer<typeof schema>;

export default function LoginScreen() {
  const router = useRouter();
  const setSignedIn = useAuthStore((s) => s.setSignedIn);
  const {
    control,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { email: '', password: '' },
  });

  const onSubmit = handleSubmit(async (values) => {
    try {
      const { token, user } = await authApi.login({ ...values, device_name: 'rn' });
      await secureStore.set(TOKEN_KEY, token);
      setSignedIn(token, user);
      router.replace('/dashboard');
    } catch (e) {
      setError('email', { message: (e as Error).message });
    }
  });

  return (
    <Screen>
      <KeyboardAvoidingView behavior={Platform.OS === 'ios' ? 'padding' : undefined} style={{ flex: 1 }}>
        <View style={styles.card}>
          <Text variant="headlineMedium" style={styles.title}>
            Sign in
          </Text>

          <Controller
            control={control}
            name="email"
            render={({ field }) => (
              <View>
                <TextInput
                  label="Email"
                  mode="outlined"
                  autoCapitalize="none"
                  keyboardType="email-address"
                  autoComplete="email"
                  value={field.value}
                  onChangeText={field.onChange}
                  error={!!errors.email}
                />
                {errors.email ? <HelperText type="error">{errors.email.message}</HelperText> : null}
              </View>
            )}
          />

          <Controller
            control={control}
            name="password"
            render={({ field }) => (
              <View>
                <TextInput
                  label="Password"
                  mode="outlined"
                  secureTextEntry
                  autoComplete="password"
                  value={field.value}
                  onChangeText={field.onChange}
                  error={!!errors.password}
                />
                {errors.password ? <HelperText type="error">{errors.password.message}</HelperText> : null}
              </View>
            )}
          />

          <Button mode="contained" onPress={onSubmit} loading={isSubmitting} style={styles.btn}>
            Sign in
          </Button>
          <Button mode="text" onPress={() => router.push('/register')} disabled={isSubmitting}>
            Create an account
          </Button>
        </View>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { maxWidth: 420, width: '100%', alignSelf: 'center', gap: 8, marginTop: 32 },
  title: { marginBottom: 16 },
  btn: { marginTop: 8 },
});
