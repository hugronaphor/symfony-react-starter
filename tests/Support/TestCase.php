<?php

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;

abstract class TestCase extends SymfonyWebTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);

        // Ensure schema exists and database is clean
        $this->ensureSchemaExists();
        $this->cleanDatabase();
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->entityManager->close();
    }

    protected function ensureSchemaExists(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('PRAGMA foreign_keys = OFF');

        try {
            $metadatas = $this->entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
            $schemaTool->dropSchema($metadatas);
            $schemaTool->createSchema($metadatas);
        } finally {
            $connection->executeQuery('PRAGMA foreign_keys = ON');
        }
    }

    protected function cleanDatabase(): void
    {
        $connection = $this->entityManager->getConnection();
        $connection->executeQuery('PRAGMA foreign_keys = OFF');

        $schemaManager = $connection->createSchemaManager();
        $tables = $schemaManager->listTableNames();

        foreach ($tables as $table) {
            $connection->executeQuery("DELETE FROM {$table}");
        }

        $connection->executeQuery('PRAGMA foreign_keys = ON');
    }
}
