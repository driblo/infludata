import { useCallback } from 'react';

import { connectionsApi } from '@/api/endpoints/connections';

/**
 * Web Phyllo Connect hook.
 *
 * Loads the Phyllo Connect web SDK on demand and opens its modal. Falls
 * back to a console warning when the SDK isn't installed (greenfield dev).
 */
export function usePhylloConnect(onConnected: () => void) {
  return useCallback(async () => {
    const token = await connectionsApi.mintPhylloToken();
    try {
      // @ts-expect-error optional dep, present in production web build only
      const mod = await import('@phyllo/connect-web');
      const initialize = (mod as { initialize: (o: object) => { open: () => void; on: (e: string, cb: () => void) => void } })
        .initialize;
      const handle = initialize({
        clientDisplayName: 'infludata',
        environment: 'sandbox',
        userId: token.phyllo_user_id,
        token: token.sdk_token,
      });
      handle.on('accountConnected', onConnected);
      handle.open();
    } catch {
      if (typeof console !== 'undefined') {
        console.warn(
          'Phyllo Connect Web SDK not available. `npm i @phyllo/connect-web` to enable.',
        );
      }
    }
  }, [onConnected]);
}
