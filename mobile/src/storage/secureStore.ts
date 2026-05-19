// Typing shim: TypeScript can't resolve `.native.ts`/`.web.ts` extensions
// natively. Metro picks the correct platform variant at bundle time.
// `secureStore.native.ts` and `secureStore.web.ts` both export the same
// shape, so we type against the native one here.
export { secureStore, TOKEN_KEY } from './secureStore.native';
