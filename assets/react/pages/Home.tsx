import React from "react";
import { useAuth } from "@/hooks/useAuth";

export default function Home() {
    const { user, isAuthenticated, isLoading } = useAuth();

    return (
        <div className="container mx-auto px-4 py-8">
            <div className="max-w-4xl mx-auto">
                <h1 className="text-3xl font-bold text-gray-100 mb-6">
                    Welcome to Symfony-React
                </h1>

                <div className="bg-gray-900 rounded-lg p-6 border border-gray-800">
                    {isLoading ? (
                        <div className="animate-pulse">
                            <div className="h-6 bg-gray-800 rounded w-3/4 mb-4"></div>
                            <div className="h-4 bg-gray-800 rounded w-1/2"></div>
                        </div>
                    ) : isAuthenticated && user ? (
                        <div>
                            <p className="text-gray-300 mb-4">
                                Hello, <span className="text-gray-100 font-semibold">{user.email}</span>!
                            </p>
                            <p className="text-gray-400">
                                You're successfully authenticated using Symfony sessions with React.
                            </p>
                        </div>
                    ) : (
                        <div>
                            <p className="text-gray-300 mb-4">
                                This is a demo of session sharing between Symfony and React.
                            </p>
                            <a
                                href="/login"
                                className="inline-block bg-gray-800 text-gray-100 px-4 py-2 rounded hover:bg-gray-700 transition-colors"
                            >
                                Sign in to continue
                            </a>
                        </div>
                    )}
                </div>

                <div className="mt-8 text-gray-400 text-sm">
                    <p>This application demonstrates:</p>
                    <ul className="list-disc list-inside mt-2 space-y-1">
                        <li>Symfony handling authentication</li>
                        <li>React consuming the authenticated session</li>
                        <li>API endpoints protected by session</li>
                    </ul>
                </div>
            </div>
        </div>
    );
}
