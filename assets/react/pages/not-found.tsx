export default function NotFound() {
    return (
        <div className="container mx-auto px-4 py-16">
            <div className="max-w-2xl mx-auto text-center">
                <h1 className="text-6xl font-bold text-gray-100 mb-4">404</h1>
                <p className="text-xl text-gray-400 mb-8">Page not found</p>
                <a
                    href="/"
                    className="inline-block bg-gray-800 text-gray-100 px-4 py-2 rounded hover:bg-gray-700 transition-colors"
                >
                    Go back home
                </a>
            </div>
        </div>
    );
}
