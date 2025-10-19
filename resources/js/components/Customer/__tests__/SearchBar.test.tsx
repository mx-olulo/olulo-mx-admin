// @TEST:STORE-LIST-001 | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SearchBar from '../SearchBar';

describe('SearchBar', () => {
    it('should render search input with placeholder', () => {
        render(<SearchBar value="" onChange={vi.fn()} placeholder="Search stores..." />);
        const input = screen.getByPlaceholderText('Search stores...');
        expect(input).toBeInTheDocument();
    });

    it('should display the current value', () => {
        render(<SearchBar value="test query" onChange={vi.fn()} />);
        const input = screen.getByRole('textbox');
        expect(input).toHaveValue('test query');
    });

    it('should call onChange when user types', async () => {
        const onChange = vi.fn();
        const user = userEvent.setup();
        render(<SearchBar value="" onChange={onChange} />);

        const input = screen.getByRole('textbox');
        await user.type(input, 'test');

        expect(onChange).toHaveBeenCalled();
        // Note: onChange is called with incremental values ('t', 'te', 'tes', 'test')
        expect(onChange).toHaveBeenCalledWith('t');
    });

    it('should have proper aria-label for accessibility', () => {
        render(<SearchBar value="" onChange={vi.fn()} />);
        const input = screen.getByLabelText('Search stores');
        expect(input).toBeInTheDocument();
    });
});
