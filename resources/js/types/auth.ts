export type UserRole = 'admin' | 'cashier';

export type User = {
    id: number;
    username: string;
    name: string;
    role: UserRole;
    is_active: boolean;
    avatar?: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};
