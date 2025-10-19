import { createRoot } from "react-dom/client";
import App from "./App";
import "./index.css";

import { QueryClientProvider } from "@tanstack/react-query";
import { queryClient } from "./lib/queryClient";
import { ThemeProvider } from "./components/ThemeProvider";

const container = document.getElementById('root');

if (container) {
    const root = createRoot(container);
    root.render(
        <QueryClientProvider client={queryClient}>
            <ThemeProvider defaultTheme="light">
                <App />
            </ThemeProvider>
        </QueryClientProvider>
    );
} else {
    console.error('Root element not found');
}
