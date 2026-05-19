import '@testing-library/jest-native/extend-expect';

// Silence common RN test warnings.
jest.mock('react-native-reanimated', () => require('react-native-reanimated/mock'));

jest.mock('expo-secure-store', () => ({
  getItemAsync: jest.fn(async () => null),
  setItemAsync: jest.fn(async () => undefined),
  deleteItemAsync: jest.fn(async () => undefined),
}));

jest.mock('expo-router', () => ({
  ...jest.requireActual('expo-router'),
  useRouter: () => ({
    push: jest.fn(),
    replace: jest.fn(),
    back: jest.fn(),
  }),
  Redirect: () => null,
  useSegments: () => [],
  Link: ({ children }: { children: React.ReactNode }) => children,
}));
