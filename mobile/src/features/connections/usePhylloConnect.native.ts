import { useCallback } from 'react';

import { connectionsApi } from '@/api/endpoints/connections';

/**
 * Native Phyllo Connect hook.
 *
 * Mints an SDK token via the backend and opens the official Phyllo Connect
 * native modal via `react-native-phyllo-connect`. We load that module
 * dynamically so the web build doesn't try to bundle native modules.
 *
 * Wiring requires:
 *   - npm i react-native-phyllo-connect
 *   - Expo dev client (NOT Expo Go) — config plugin added in app.config.ts
 */
type PhylloModule = {
  initialize: (opts: {
    clientDisplayName: string;
    environment: string;
    userId: string;
    token: string;
  }) => void;
  open: () => void;
  onAccountConnected?: (cb: () => void) => void;
};

export function usePhylloConnect(onConnected: () => void) {
  return useCallback(async () => {
    const token = await connectionsApi.mintPhylloToken();
    try {
      // Optional native dep — dynamic require keeps TS from resolving it
      // when the module isn't installed in dev sandboxes.
       
      const phyllo = require('react-native-phyllo-connect') as PhylloModule;
      phyllo.initialize({
        clientDisplayName: 'infludata',
        environment: 'sandbox',
        userId: token.phyllo_user_id,
        token: token.sdk_token,
      });
      if (phyllo.onAccountConnected) phyllo.onAccountConnected(onConnected);
      phyllo.open();
    } catch {
      throw new Error(
        'Phyllo Connect SDK not available — run `npx expo prebuild` and rebuild the dev client.',
      );
    }
  }, [onConnected]);
}
