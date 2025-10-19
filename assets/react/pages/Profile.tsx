import { useAuth } from "@/hooks/useAuth";
import { Key, ReactElement, JSXElementConstructor, ReactNode, ReactPortal } from "react";

export default function Profile() {
    const {user, isLoading, isAuthenticated} = useAuth();

    if (isLoading) {
        return (
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-2xl mx-auto">
                    <div className="bg-gray-900 rounded-lg p-6 border border-gray-800 animate-pulse">
                        <div className="h-8 bg-gray-800 rounded w-1/3 mb-4"></div>
                        <div className="h-4 bg-gray-800 rounded w-1/2 mb-2"></div>
                        <div className="h-4 bg-gray-800 rounded w-1/4"></div>
                    </div>
                </div>
            </div>
        );
    }

    if (!isAuthenticated || !user) {
        return (
            <div className="container mx-auto px-4 py-8">
                <div className="max-w-2xl mx-auto">
                    <div className="bg-gray-900 rounded-lg p-6 border border-gray-800 text-center">
                        <p className="text-gray-300 mb-4">Please sign in to view your profile.</p>
                        <a
                            href="/login"
                            className="inline-block bg-gray-800 text-gray-100 px-4 py-2 rounded hover:bg-gray-700 transition-colors"
                        >
                            Sign In
                        </a>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="max-w-2xl mx-auto">
                <h1 className="text-3xl font-bold text-gray-100 mb-6">My Profile</h1>

                <div className="bg-gray-900 rounded-lg p-6 border border-gray-800">
                    <div className="space-y-4">
                        <div>
                            <label className="text-gray-400 text-sm">User ID</label>
                            <p className="text-gray-100">{user.id}</p>
                        </div>

                        <div>
                            <label className="text-gray-400 text-sm">Email</label>
                            <p className="text-gray-100">{user.email}</p>
                        </div>

                        <div>
                            <label className="text-gray-400 text-sm">Roles</label>
                            <div className="flex flex-wrap gap-2 mt-1">
                                {user.roles.map((role) => (
                                    <span
                                        key={role}
                                        className="bg-gray-800 text-gray-300 px-2 py-1 rounded text-sm"
                                    >
                                        {role}
                                    </span>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>

                <div className="mt-6">
                    <a
                        href="/logout"
                        className="inline-block bg-gray-800 text-gray-100 px-4 py-2 rounded hover:bg-gray-700 transition-colors"
                    >
                        Sign Out
                    </a>
                </div>
            </div>
        </div>
    );
}
