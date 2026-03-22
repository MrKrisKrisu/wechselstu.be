export interface Station {
    id: string;
    name: string;
    location: string;
    token: string;
    created_at?: string;
}

export interface TicketDenomination {
    id: string;
    denomination_cents: number;
    quantity: number;
}

export type TicketType = 'cash_full' | 'change_request' | 'other';
export type TicketStatus = 'open' | 'accepted' | 'done';

export interface Ticket {
    id: string;
    type: TicketType;
    type_label: string;
    status: TicketStatus;
    status_label: string;
    message: string | null;
    station: Station;
    denominations: TicketDenomination[];
    assigned_to: string | null;
    assigned_user: { id: string; name: string } | null;
    accepted_at: string | null;
    done_at: string | null;
    created_at: string;
}

export interface DashboardAccess {
    id: string;
    label: string;
    token: string;
    created_at: string;
}

export interface User {
    id: string;
    name: string;
    email: string;
}

export const DENOMINATIONS: { cents: number; label: string }[] = [
    { cents: 50, label: '0,50 €' },
    { cents: 100, label: '1,00 €' },
    { cents: 200, label: '2,00 €' },
    { cents: 500, label: '5,00 €' },
    { cents: 1000, label: '10,00 €' },
];
