export type TransactionType =
    | 'income'
    | 'expense'
    | 'transfer'
    | 'inter_entity_transfer'
    | 'adjustment';

export type TransactionStatus = 'draft' | 'pending_approval' | 'approved';

export type TransactionSummary = {
    id: string;
    date: string;
    description: string | null;
    type: TransactionType;
    amount: string;
    status: TransactionStatus;
    category: { id: string; name: string } | null;
    creator: { id: string; name: string } | null;
};

export type TransactionEntry = {
    id: string;
    account: { id: string; name: string; type: string };
    debit: string;
    kredit: string;
};

export type TransactionAttachment = {
    id: string;
    original_name: string | null;
    mime_type: string | null;
    size: number | null;
    url: string;
};

export type TransactionDetail = TransactionSummary & {
    reference: string | null;
    entries: TransactionEntry[];
    attachments: TransactionAttachment[];
};

export type AccountOption = {
    id: string;
    name: string;
    type: string;
};

export type CategoryOption = {
    id: string;
    name: string;
};

export type OtherEntityOption = {
    id: string;
    name: string;
    type: string;
    accounts: AccountOption[];
};
