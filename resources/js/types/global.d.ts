import type { Auth } from '@/types/auth';
import type { Entity } from '@/types/entity';

declare module 'react' {
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            sidebarOpen: boolean;
            activeEntity: Entity | null;
            entities: Entity[] | null;
            canCreateEntity: boolean;
            canCreatePersonalEntity: boolean;
            [key: string]: unknown;
        };
    }
}
