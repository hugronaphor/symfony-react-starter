import {useQuery} from "@tanstack/react-query";
import {User} from "@/shared/schema";

async function fetchUser(): Promise<User | null> {
    const response = await fetch("/api/profile/me", {
        credentials: "include",
    });

    if (response.status === 401) {
        return null; // not authenticated
    }

    if (!response.ok) {
        throw new Error("Failed to fetch user");
    }

    return await response.json();
}

export function useAuth() {
    const {data: user, isLoading, error} = useQuery<User | null, Error>({
        queryKey: ['/api/profile/me'],
        queryFn: () => fetchUser(),
    });

    return {
        user,
        isLoading,
        isAuthenticated: !!user,
        error,
    };
}

