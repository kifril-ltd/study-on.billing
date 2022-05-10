<?php

namespace App\Controller;

use App\Dto\Response\Transfromer\CurrentUserResponseTransformer;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('api/v1/users')]
class ApiUserController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/current', name: 'current_user', methods: ['GET'])]
    public function getCurrentUser(): Response
    {
        $user = $this->security->getUser();

        if (!$user) {
            return $this->json([
                'status_code' => Response::HTTP_NOT_FOUND,
                'message' => 'User doesn\'t exists.'
            ], Response::HTTP_NOT_FOUND);
        }

        $userDto = (new CurrentUserResponseTransformer())->transformFromObject($user);
        return $this->json([
            $userDto
        ], Response::HTTP_OK);
    }
}
