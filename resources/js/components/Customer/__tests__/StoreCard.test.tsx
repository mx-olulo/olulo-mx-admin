// @TEST:STORE-LIST-001 | SPEC: .moai/specs/SPEC-STORE-LIST-001/spec.md

import { describe, it, expect, vi } from 'vitest';
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import StoreCard from '../StoreCard';
import type { Store } from '@/types';

describe('StoreCard', () => {
    const mockStore: Store = {
        id: 1,
        organization_id: 1,
        name: 'Test Store',
        description: 'This is a test store description',
        address: '123 Test St',
        phone: '123-456-7890',
        is_active: true,
        created_at: '2025-01-01T00:00:00Z',
        updated_at: '2025-01-01T00:00:00Z',
        organization: {
            id: 1,
            name: 'Test Organization',
            is_active: true,
            created_at: '2025-01-01T00:00:00Z',
            updated_at: '2025-01-01T00:00:00Z',
        },
    };

    it('should render store name', () => {
        render(<StoreCard store={mockStore} />);
        expect(screen.getByText('Test Store')).toBeInTheDocument();
    });

    it('should render organization badge', () => {
        render(<StoreCard store={mockStore} />);
        expect(screen.getByText('Test Organization')).toBeInTheDocument();
    });

    it('should render description', () => {
        render(<StoreCard store={mockStore} />);
        expect(screen.getByText('This is a test store description')).toBeInTheDocument();
    });

    it('should show active indicator when store is active', () => {
        render(<StoreCard store={mockStore} />);
        const activeIndicator = screen.getByLabelText('Active store');
        expect(activeIndicator).toBeInTheDocument();
    });

    it('should not show active indicator when store is inactive', () => {
        const inactiveStore = { ...mockStore, is_active: false };
        render(<StoreCard store={inactiveStore} />);
        const activeIndicator = screen.queryByLabelText('Active store');
        expect(activeIndicator).not.toBeInTheDocument();
    });

    it('should call onClick when clicked', async () => {
        const onClick = vi.fn();
        const user = userEvent.setup();
        render(<StoreCard store={mockStore} onClick={onClick} />);

        const card = screen.getByRole('button');
        await user.click(card);

        expect(onClick).toHaveBeenCalledTimes(1);
    });
});
