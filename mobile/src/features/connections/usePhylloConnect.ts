// Typing shim for platform-specific resolution. See secureStore.ts for the
// rationale; Metro resolves the `.native.ts` or `.web.ts` variant at build.
export { usePhylloConnect } from './usePhylloConnect.native';
