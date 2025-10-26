<?php

namespace App\Tests\Support;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class FunctionalTestCase extends WebTestCase
{
    protected static function initializeSchema(): void
    {
        $container = static::getContainer();
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();

        $connection->executeStatement('PRAGMA foreign_keys = OFF');
        try {
            $metadatas = $entityManager->getMetadataFactory()->getAllMetadata();
            $schemaTool = new SchemaTool($entityManager);
            $schemaTool->dropSchema($metadatas);
            $schemaTool->createSchema($metadatas);
        } finally {
            $connection->executeStatement('PRAGMA foreign_keys = ON');
        }

        $entityManager->clear();
    }

    protected function authenticateUser(KernelBrowser $client, string $email, string $password): void
    {
        $crawler = $client->request('GET', '/login');
        $csrfToken = $crawler->filter('input[name="_csrf_token"]')->attr('value');

        $client->request('POST', '/login', [
            'email' => $email,
            'password' => $password,
            '_csrf_token' => $csrfToken,
        ]);

        if ($client->getResponse()->isRedirect()) {
            $client->followRedirect();
        }
    }
}
