import type { AxiosError } from 'axios';

/** Problem+JSON envelope returned by the Laravel API. */
export type ProblemBody = {
  type?: string;
  title?: string;
  status?: number;
  detail?: string;
  trace_id?: string | null;
};

export class AppError extends Error {
  readonly status: number;
  readonly title: string;
  readonly detail: string;
  readonly traceId: string | null;

  constructor(opts: { status: number; title: string; detail: string; traceId?: string | null }) {
    super(opts.detail || opts.title);
    this.name = 'AppError';
    this.status = opts.status;
    this.title = opts.title;
    this.detail = opts.detail;
    this.traceId = opts.traceId ?? null;
  }
}

export function appErrorFromAxios(err: unknown): AppError {
  const axiosErr = err as AxiosError<ProblemBody>;
  if (axiosErr?.response) {
    const body = axiosErr.response.data ?? {};
    return new AppError({
      status: axiosErr.response.status,
      title: body.title ?? axiosErr.response.statusText ?? 'Error',
      detail: body.detail ?? axiosErr.message,
      traceId: body.trace_id ?? null,
    });
  }
  if (axiosErr?.request) {
    return new AppError({ status: 0, title: 'NetworkError', detail: axiosErr.message });
  }
  return new AppError({ status: -1, title: 'UnknownError', detail: String(err) });
}
