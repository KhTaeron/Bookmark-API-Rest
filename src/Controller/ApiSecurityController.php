<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiSecurityController extends AbstractController
{
    /**
     * @Route("/api/security", name="app_api_security")
     */
    public function index(): JsonResponse
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/ApiSecurityController.php',
        ]);
    }


    /**
     * @Route("/api/login", name="api_login", methods={"POST"})
     */
    public function login(Request $request): JsonResponse
    {
        $user = $this->getUser();
        if ($user === null) {
            return $this->json([
                'message' => 'missing creddentials',
                'request' => $request->getContent()
            ], JsonResponse::HTTP_UNAUTHORIZED);

        }
        return $this->json([
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
    /**
     * @Route("/api/test_login", name="api_logtest", methods={"GET"})
     */
    public function logtest(): JsonResponse
    {
        $user = $this->getUser();

        if (null === $user) {
            return $this->json(['logged' => 'no'], JsonResponse::HTTP_UNAUTHORIZED);
        }

        return $this->json(['logged' => 'yes', 'username' => $user->getUserIdentifier()]);
    }

    /**
     * @Route("/api/logout", name="api_logout", methods={"GET"})
     */
    public function logout(): never
    {

    }
}
