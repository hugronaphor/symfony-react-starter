<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\SerializerInterface;

#[Route('/api')]
class ProfileController extends AbstractController
{
    #[Route('/profile/me', name: 'api_profile_me', methods: ['GET'])]
    public function me(SerializerInterface $serializer): JsonResponse
    {
        $user = $this->getUser();

        if (!$user) {
            return $this->json(
                ['message' => 'Not authenticated'],
                Response::HTTP_UNAUTHORIZED
            );
        }

        $jsonContent = $serializer->serialize($user, 'json', [
            'groups' => ['profile'],
        ]);

        return new JsonResponse($jsonContent, Response::HTTP_OK, [], true);
    }
}
