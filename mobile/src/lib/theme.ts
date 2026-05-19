import { MD3DarkTheme, configureFonts, type MD3Theme } from 'react-native-paper';

const brand = {
  primary: '#7B61FF',
  surface: '#1A1D40',
  background: '#0F1226',
  danger: '#D62F4E',
  success: '#22C55E',
  muted: '#7C82A1',
  onSurface: '#FFFFFF',
};

export const paperTheme: MD3Theme = {
  ...MD3DarkTheme,
  colors: {
    ...MD3DarkTheme.colors,
    primary: brand.primary,
    onPrimary: '#FFFFFF',
    primaryContainer: '#2A2065',
    onPrimaryContainer: '#E8E0FF',
    secondary: '#9F8BFF',
    onSecondary: '#FFFFFF',
    background: brand.background,
    onBackground: brand.onSurface,
    surface: brand.surface,
    onSurface: brand.onSurface,
    surfaceVariant: '#2A2E55',
    onSurfaceVariant: '#C8CDE8',
    outline: '#2A2E55',
    outlineVariant: '#1F2240',
    error: brand.danger,
    onError: '#FFFFFF',
    elevation: {
      ...MD3DarkTheme.colors.elevation,
      level1: '#171A38',
      level2: '#1A1D40',
      level3: '#1F2249',
    },
  },
  fonts: configureFonts({ config: { fontFamily: 'System' } }),
};

export const palette = brand;
