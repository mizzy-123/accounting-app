export type InvoiceStatus = 'draft' | 'sent' | 'paid';

export type InvoiceItem = {
    name: string;
    qty: number;
    price: number;
    subtotal: number;
};

export type InvoiceSummary = {
    id: string;
    invoice_number: string;
    status: InvoiceStatus;
    total: string;
    subtotal: string;
    discount: string;
    issued_date: string;
    due_date: string | null;
    paid_at: string | null;
    is_overdue: boolean;
    days_until_due: number | null;
    project: { id: string; name: string } | null;
    client: { id: string; name: string } | null;
};

export type InvoiceDetail = InvoiceSummary & {
    notes: string | null;
    items: InvoiceItem[];
    creator: { id: string; name: string } | null;
};

export type InvoiceReminder = {
    id: string;
    invoice_number: string;
    client_name: string | null;
    total: string;
    due_date: string | null;
    days_until_due: number | null;
    severity: 'overdue' | 'due_soon';
};

export type BusinessOverview = {
    active_projects: {
        id: string;
        name: string;
        status: string;
        budget: string | null;
        client_name: string | null;
    }[];
    receivables: {
        id: string;
        invoice_number: string;
        client_name: string | null;
        total: string;
        due_date: string | null;
        status: string;
        is_overdue: boolean;
    }[];
};
