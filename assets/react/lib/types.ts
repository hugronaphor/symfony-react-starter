import { User } from "@/shared/schema";

export interface AuthUser extends User {
    displayName?: string;
}
