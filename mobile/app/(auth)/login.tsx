import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'expo-router';
import { Controller, useForm } from 'react-hook-form';
import { KeyboardAvoidingView, Platform, StyleSheet, Text, View } from 'react-native';
import { z } from 'zod';

import { authApi } from '@/api/endpoints/auth';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { Button } from '@/ui/Button';
import { Screen } from '@/ui/Screen';
import { TextField } from '@/ui/TextField';

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
          <Text style={styles.title}>Sign in</Text>
          <Controller
            control={control}
            name="email"
            render={({ field }) => (
              <TextField
                label="Email"
                autoCapitalize="none"
                keyboardType="email-address"
                autoComplete="email"
                value={field.value}
                onChangeText={field.onChange}
                errorText={errors.email?.message}
              />
            )}
          />
          <Controller
            control={control}
            name="password"
            render={({ field }) => (
              <TextField
                label="Password"
                secureTextEntry
                autoComplete="password"
                value={field.value}
                onChangeText={field.onChange}
                errorText={errors.password?.message}
              />
            )}
          />
          <Button label="Sign in" onPress={onSubmit} loading={isSubmitting} />
          <Button label="Create an account" variant="outlined" onPress={() => router.push('/register')} />
        </View>
      </KeyboardAvoidingView>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { maxWidth: 420, width: '100%', alignSelf: 'center', gap: 8, marginTop: 32 },
  title: { color: '#fff', fontSize: 24, fontWeight: '700', marginBottom: 16 },
});
