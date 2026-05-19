import type { AxiosError } from 'axios';

import { appErrorFromAxios, AppError } from '@/api/errors';

describe('appErrorFromAxios', () => {
  it('unwraps a Problem+JSON body', () => {
    const axiosErr = {
      response: {
        status: 422,
        statusText: 'Unprocessable',
        data: { type: 'about:blank', title: 'ValidationException', status: 422, detail: 'email required', trace_id: 't-1' },
      },
      message: 'request failed',
    } as unknown as AxiosError;

    const err = appErrorFromAxios(axiosErr);
    expect(err).toBeInstanceOf(AppError);
    expect(err.status).toBe(422);
    expect(err.title).toBe('ValidationException');
    expect(err.detail).toBe('email required');
    expect(err.traceId).toBe('t-1');
  });

  it('falls back to NetworkError when there is no response', () => {
    const err = appErrorFromAxios({ request: {}, message: 'timeout' });
    expect(err.status).toBe(0);
    expect(err.title).toBe('NetworkError');
  });

  it('handles an unknown error', () => {
    const err = appErrorFromAxios(new Error('boom'));
    expect(err.status).toBe(-1);
    expect(err.title).toBe('UnknownError');
  });
});
