export type User = {
    "@id"?: string;
    "@type"?: "User";
    id: number | string;
    email: string;
    roles: string[];
};

