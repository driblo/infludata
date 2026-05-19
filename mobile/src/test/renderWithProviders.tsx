import { render, type RenderOptions } from '@testing-library/react-native';
import { type ReactElement } from 'react';
import { PaperProvider } from 'react-native-paper';

import { paperTheme } from '@/lib/theme';

function AllProviders({ children }: { children: React.ReactNode }) {
  return <PaperProvider theme={paperTheme}>{children}</PaperProvider>;
}

export function renderWithProviders(ui: ReactElement, options?: RenderOptions) {
  return render(ui, { wrapper: AllProviders, ...options });
}
