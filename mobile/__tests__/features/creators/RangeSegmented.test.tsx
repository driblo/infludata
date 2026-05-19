import { fireEvent, screen } from '@testing-library/react-native';

import { RangeSegmented } from '@/features/creators/RangeSegmented';
import { renderWithProviders } from '@/test/renderWithProviders';

describe('RangeSegmented', () => {
  it('renders all four range buttons and reports selection', () => {
    const onChange = jest.fn();
    renderWithProviders(<RangeSegmented value="30d" onChange={onChange} />);

    expect(screen.getByText('7d')).toBeTruthy();
    expect(screen.getByText('30d')).toBeTruthy();
    expect(screen.getByText('90d')).toBeTruthy();
    expect(screen.getByText('1y')).toBeTruthy();

    fireEvent.press(screen.getByText('90d'));
    expect(onChange).toHaveBeenCalledWith('90d');
  });
});
