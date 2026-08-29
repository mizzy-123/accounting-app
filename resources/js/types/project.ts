export type ProjectStatus = 'active' | 'completed' | 'cancelled';

export type Client = {
    id: string; // UUID
    entity_id: string;
    name: string;
    contact_info: string | null;
    projects_count?: number;
    transactions_count?: number;
};

export type Project = {
    id: string; // UUID
    entity_id: string;
    client_id: string | null;
    client: { id: string; name: string } | null;
    name: string;
    budget: string | null;
    start_date: string | null;
    end_date: string | null;
    status: ProjectStatus;
    total_revenue?: string;
    total_expense?: string;
    net_profit?: string;
    budget_used_percent?: number;
    is_over_budget?: boolean;
    transactions_count?: number;
};
