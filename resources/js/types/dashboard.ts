export type DashboardAccount = {
    id: string;
    name: string;
    type: string;
    balance: string;
};

export type DashboardTotals = {
    total_assets: string;
    income_this_month: string;
    expense_this_month: string;
    net_this_month: string;
};

export type CashFlowPoint = {
    label: string;
    income: string;
    expense: string;
};

export type CategoryExpense = {
    name: string;
    amount: string;
};

export type RecentTransaction = {
    id: string;
    date: string;
    description: string | null;
    type: string;
    amount: string;
};

export type DashboardChart = {
    weekly: CashFlowPoint[];
    monthly: CashFlowPoint[];
};
