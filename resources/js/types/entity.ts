export type EntityRole = 'owner' | 'member' | 'viewer';

export type EntityType = 'personal' | 'business';

export type Entity = {
    id: string; // UUID
    name: string;
    type: EntityType;
    role: EntityRole;
};
