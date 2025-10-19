# Architecture & Implementation Details

## Overview

This project demonstrates a hybrid architecture where Symfony serves as both:
1. A traditional server-side framework (handling authentication, sessions)
2. An API backend for a React Single Page Application (SPA)

The key is **session sharing** - users authenticate via Symfony's traditional form login, and the React app can access the authenticated session through API endpoints.

## Core Architecture Principles

### 1. Unified Routing Strategy

The routing is handled by a priority system:
- **Specific routes first**: `/api/*`, `/login`, `/logout`, `/_profiler/*`, `/_wdt/*`
- **Catch-all route last**: Everything else goes to React
```php
#[Route(
    '/{reactRouting}',
    name: 'app_react',
    requirements: ['reactRouting' => '^(?!api|login|logout|_(profiler|wdt)).*'],
    priority: -1
)]
```

The negative lookahead regex `(?!api|login|logout|...)` ensures React doesn't capture system routes.

### 2. Authentication Flow
```
User Journey:
1. User visits any route → Not authenticated → Redirected to /login
2. User submits credentials → Symfony validates → Creates PHP session
3. Session cookie (PHPSESSID) is set with httpOnly flag
4. User redirected to React app → Cookie persists
5. React calls /api/profile/me → Cookie sent automatically
6. Symfony reads session → Returns user data
```

### 3. Session Sharing Mechanism

#### Symfony Side:
- Uses standard PHP sessions via `session` handler
- Stores user object in session after authentication
- Session cookie is `httpOnly` (not accessible to JavaScript)
- `FormAuthenticator` handles login and redirects

#### React Side:
- Uses `fetch` with `credentials: "include"` to send cookies
- Calls `/api/profile/me` to check authentication status
- Never handles passwords or sensitive auth data directly

### 4. Security Configuration
```yaml
security:
    firewalls:
        main:
            custom_authenticator: App\Security\FormAuthenticator
            logout:
                path: app_logout
                target: app_login
    
    access_control:
        - { path: ^/login$, roles: PUBLIC_ACCESS }
        - { path: ^/api/, roles: IS_AUTHENTICATED_FULLY }
```

All API routes require full authentication, enforced at the framework level.

## Project Structure
```
src/
├── Controller/
│   ├── Api/
│   │   └── ProfileController.php    # API endpoints
│   ├── React/
│   │   └── DefaultController.php    # React catch-all
│   └── SecurityController.php       # Login/logout
├── Entity/
│   └── User.php                     # User entity with groups
├── Security/
│   └── FormAuthenticator.php       # Custom authenticator
└── Repository/
    └── UserRepository.php           # User data access

assets/
├── react/
│   ├── components/                  # React components
│   ├── hooks/                       # Custom React hooks
│   ├── pages/                       # Page components
│   └── App.tsx                      # Main React app
└── app.js                          # Webpack entry point
```

## Key Components Explained

### 1. FormAuthenticator

Extends `AbstractLoginFormAuthenticator` to:
- Extract credentials from login form
- Create authentication passport

### 2. React DefaultController

The catch-all controller that:
- Requires authentication (`#[IsGranted('IS_AUTHENTICATED_FULLY')]`)
- Renders the React app template
- Let's React Router handle client-side routing

### 3. ProfileController

Simple API endpoint that:
- Returns current user data
- Uses serialization groups to control exposed fields
- Returns 401 if not authenticated

### 4. useAuth Hook (React)
```typescript
export function useAuth() {
    const { data: user, isLoading } = useQuery({
        queryKey: ['user'],
        queryFn: async () => {
            const response = await fetch('/api/profile/me', {
                credentials: 'include'
            });
            if (response.status === 401) return null;
            return response.json();
        }
    });
    
    return { user, isAuthenticated: !!user, isLoading };
}
```

## Serialization Groups

The User entity uses groups to control data exposure:
- `user:read` - General read operations
- `user:write` - Create/update operations
- `user:list` - List views (minimal data)
- `profile` - Current user profile endpoint

## Production Considerations

1. **CORS**: Not needed since everything is same-origin
2. **CSRF**: Handled by Symfony forms, API uses session auth
3. **XSS**: React handles escaping, session cookie is httpOnly
4. **Session security**:
    - Passwords hashed with bcrypt
    - Session timeout configured in `framework.yaml`

## Extension Points

This architecture easily extends to:
- Add more API endpoints (just add controllers)
- Implement OAuth providers (add to security config)
- Add WebSocket support (session cookie works with WS)
- Separate frontend/backend deployments (add CORS config)
- Add GraphQL (API Platform compatible)

## Why This Architecture?

**Benefits:**
- Single deployment unit
- Shared sessions (no JWT complexity)
- Server-side rendered auth pages (works without JS)
- React gets full SPA benefits
- Symfony's security is battle-tested
- Easy local development

**Trade-offs:**
- Frontend/backend coupled in deployment
- Not "pure" API-first architecture
