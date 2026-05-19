import { SegmentedButtons } from 'react-native-paper';

import { RANGES, type Range } from '@/api/types';

export function RangeSegmented({ value, onChange }: { value: Range; onChange: (r: Range) => void }) {
  return (
    <SegmentedButtons
      value={value}
      onValueChange={(v) => onChange(v as Range)}
      density="small"
      buttons={RANGES.map((r) => ({ value: r, label: r }))}
    />
  );
}
