import { zodResolver } from '@hookform/resolvers/zod';
import { useRouter } from 'expo-router';
import { Controller, useForm } from 'react-hook-form';
import { StyleSheet, View } from 'react-native';
import { Button, HelperText, Text, TextInput } from 'react-native-paper';
import { z } from 'zod';

import { authApi } from '@/api/endpoints/auth';
import { useAuthStore } from '@/auth/authStore';
import { secureStore, TOKEN_KEY } from '@/storage/secureStore';
import { Screen } from '@/ui/Screen';

const schema = z.object({
  name: z.string().min(1, 'Name required'),
  email: z.string().email('Enter a valid email'),
  password: z.string().min(8, 'Min 8 characters'),
});

type FormValues = z.infer<typeof schema>;

export default function RegisterScreen() {
  const router = useRouter();
  const setSignedIn = useAuthStore((s) => s.setSignedIn);
  const {
    control,
    handleSubmit,
    setError,
    formState: { errors, isSubmitting },
  } = useForm<FormValues>({
    resolver: zodResolver(schema),
    defaultValues: { name: '', email: '', password: '' },
  });

  const onSubmit = handleSubmit(async (values) => {
    try {
      const { token, user } = await authApi.register(values);
      await secureStore.set(TOKEN_KEY, token);
      setSignedIn(token, user);
      router.replace('/dashboard');
    } catch (e) {
      setError('email', { message: (e as Error).message });
    }
  });

  return (
    <Screen>
      <View style={styles.card}>
        <Text variant="headlineMedium" style={styles.title}>
          Create account
        </Text>

        <Controller
          control={control}
          name="name"
          render={({ field }) => (
            <View>
              <TextInput
                label="Name"
                mode="outlined"
                value={field.value}
                onChangeText={field.onChange}
                error={!!errors.name}
              />
              {errors.name ? <HelperText type="error">{errors.name.message}</HelperText> : null}
            </View>
          )}
        />

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
                autoComplete="password-new"
                value={field.value}
                onChangeText={field.onChange}
                error={!!errors.password}
              />
              {errors.password ? <HelperText type="error">{errors.password.message}</HelperText> : null}
            </View>
          )}
        />

        <Button mode="contained" onPress={onSubmit} loading={isSubmitting} style={styles.btn}>
          Create account
        </Button>
        <Button mode="text" onPress={() => router.push('/login')} disabled={isSubmitting}>
          Already have an account? Sign in
        </Button>
      </View>
    </Screen>
  );
}

const styles = StyleSheet.create({
  card: { maxWidth: 420, width: '100%', alignSelf: 'center', gap: 8, marginTop: 32 },
  title: { marginBottom: 16 },
  btn: { marginTop: 8 },
});
