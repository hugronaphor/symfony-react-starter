tests/
├── Unit/                          # Pure logic, no database
├── Integration/                   # Real database, components working together
│   ├── Repository/
│   ├── Query/
│   ├── Command/
│   └── Service/
├── Functional/                    # Full HTTP stack
│   └── Controller/
├── Fixtures/                      # Shared test data factories
│   └── UserFactory.php
├── Support/                       # Shared test utilities
│   └── AuthenticatedTestCase.php
└── bootstrap.php

Separation of Concerns:

TestCase: Database setup/teardown for everyone
AuthenticatedTestCase: Adds auth user creation for tests needing it
UserFactory: DRY user creation (reusable across all test types)

Authentication-Aware:

AuthenticatedTestCase automatically creates $this->authenticatedUser
Integration tests can create additional users with $this->createUser()
Functional tests can login users with $client->loginUser($authenticatedUser)

Scalability:

Add ProductTestCase extends AuthenticatedTestCase for product-specific integration tests
Create ProductFactory following the same pattern
Create PaginationTestCase extends TestCase for shared pagination behavior
Functional tests inherit auth logic automatically
