import { fireEvent, render, screen } from '@testing-library/react-native';

import { RangeSegmented } from '@/features/creators/RangeSegmented';

describe('RangeSegmented', () => {
  it('renders all four range buttons and reports selection', () => {
    const onChange = jest.fn();
    render(<RangeSegmented value="30d" onChange={onChange} />);

    expect(screen.getByText('7d')).toBeTruthy();
    expect(screen.getByText('30d')).toBeTruthy();
    expect(screen.getByText('90d')).toBeTruthy();
    expect(screen.getByText('1y')).toBeTruthy();

    fireEvent.press(screen.getByText('90d'));
    expect(onChange).toHaveBeenCalledWith('90d');
  });
});
