import { compactNumber, formatDate } from '@/lib/format';

describe('format', () => {
  it('formats compact numbers', () => {
    expect(compactNumber(1500)).toMatch(/1\.?5K/);
    expect(compactNumber(2_500_000)).toMatch(/2\.?5M/);
  });

  it('returns empty string for null date', () => {
    expect(formatDate(null)).toBe('');
    expect(formatDate(undefined)).toBe('');
    expect(formatDate('not a date')).toBe('');
  });

  it('formats valid dates', () => {
    const out = formatDate('2026-05-19T00:00:00Z');
    expect(out.length).toBeGreaterThan(0);
  });
});
