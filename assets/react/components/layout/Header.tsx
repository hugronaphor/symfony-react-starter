import { useAuth } from "@/hooks/useAuth";
import { User } from "@/shared/schema";

export default function Header() {
    const { user, isAuthenticated, isLoading } = useAuth() as {
        user: User | undefined,
        isAuthenticated: boolean,
        isLoading: boolean
    };

    return (
        <header className="bg-gray-900 border-b border-gray-800">
            <div className="container mx-auto px-4">
                <div className="flex items-center justify-between h-16">
                    <div className="flex items-center space-x-8">
                        <a href="/" className="text-gray-100 font-semibold text-lg hover:text-gray-300 transition-colors">
                            Symfony-React
                        </a>
                        <nav className="hidden md:flex space-x-6">
                            <a href="/" className="text-gray-300 hover:text-gray-100 transition-colors">
                                Dashboard
                            </a>
                            {isAuthenticated && (
                                <a href="/profile" className="text-gray-300 hover:text-gray-100 transition-colors">
                                    Profile
                                </a>
                            )}
                        </nav>
                    </div>

                    <div className="flex items-center space-x-4">
                        {isLoading ? (
                            <div className="h-8 w-24 bg-gray-800 animate-pulse rounded"></div>
                        ) : isAuthenticated && user ? (
                            <div className="flex items-center space-x-4">
                                <span className="text-gray-400 text-sm">{user.email}</span>
                                <a
                                    href="/logout"
                                    className="text-gray-300 hover:text-gray-100 text-sm transition-colors"
                                >
                                    Sign out
                                </a>
                            </div>
                        ) : (
                            <a
                                href="/login"
                                className="bg-gray-800 text-gray-100 px-4 py-2 rounded hover:bg-gray-700 transition-colors"
                            >
                                Sign In
                            </a>
                        )}
                    </div>
                </div>
            </div>
        </header>
    );
}
