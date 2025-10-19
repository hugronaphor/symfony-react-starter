# Symfony + React Demo Project

This is a demo project demonstrating a **Symfony backend** with a **React frontend** living in the `assets` directory. The setup allows **session sharing** between Symfony and a React frontend.

- Symfony handles routes under `/api`, `/login`, `/logout`, and debug routes (`/_profiler`, `/_wdt`)
- All other routes are managed by React using a catch-all Symfony route
- Authentication is handled by Symfony with sessions accessible to React

## Quick Start with DDEV

#### Clone this repository
#### Start DDEV:
```bash
ddev start
```

#### Run the setup command:
```bash
ddev up
```

#### Create a test user:
```bash
ddev exec php bin/console app:create-user test@test test
```

That's it! Visit your site at `https://[project-name].ddev.site`


## Default Credentials

- Email: `test@test`
- Password: `test`

