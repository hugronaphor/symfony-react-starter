<?php

namespace App\Controller\React;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class DefaultController extends AbstractController
{
    #[Route(
        '/{reactRouting}',
        name: 'app_react',
        requirements: ['reactRouting' => '^(?!api|login|logout|favicon\.ico|robots\.txt|apple-touch-icon.*\.png|_(profiler|wdt)).*'],
        defaults: ['reactRouting' => null],
        priority: -1
    )]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function app(): Response
    {
        // Only authenticated users can reach here
        return $this->render('app/index.html.twig');
    }
}
