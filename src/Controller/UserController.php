<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\UrlHelper;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

class UserController extends AbstractController
{
    /**
     * @Route("/users", name="app_user")
     */
    public function index(ManagerRegistry $doctrine, UrlHelper $urlHelper, Request $request): JsonResponse
    {
        $step = 10;
        $current = 1;

        $response = new jsonResponse();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $users = $doctrine->getRepository(User::class)->findNextX($current - 1, $step);

        $baseurl = $urlHelper->getAbsoluteUrl($this->generateUrl('app_user'));

        $json_data = [];

        foreach ($users as $user) {
            $json_data['Locations'][] = $baseurl . $user->getId();
        }

        $json_data['meta'] = ["total_count" => count($users)]; // pour le contenu meta du JSON

        $nbtotal = $doctrine->getRepository(User::class)->countAll();

        // fabrication des en-tetes de reponse
        $nextpage = intdiv($current, $step) + 2;
        $response->headers->set("Link", "<" . $baseurl . "/pages/" . ">" . $nextpage . "; rel=\"next\"");
        $response->headers->set("Link", "<" . $baseurl . "/last/>; rel=\"last\"", false);
        $response->headers->set("Link", "<" . $baseurl . ">; rel=\"first\"", false);

        $nextXusers = ($current + $step <= $nbtotal) ? $current + $step : $nbtotal;
        $response->headers->set("Content-range", "urls " . $current . "-" . $nextXusers . "/" . $nbtotal);

        $response->headers->set("X-Page-Size", count($users));
        $response->headers->set("X-Page-Size", $step);
        $response->headers->set("X-Current-Page", intdiv($current, $step) + 1);


        // fin de la preparation de la reponse : inclusion du JSON et du status
        $response->setData($json_data);

        $response->setStatusCode(Response::HTTP_PARTIAL_CONTENT);
        return $response;

    }

    /**
     * @Route("/users/create", name="create_user", methods={"POST"})
     */

    public function create_user(ManagerRegistry $doctrine, Request $request, UrlHelper $urlHelper, UserPasswordHasherInterface $passwordHasher): Response
    {
        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $data = $doctrine->getRepository(User::class)->getJson($request); 
        $email = $data['email'];
        $password = $data['password'];
        $roles = $data['roles'];

        if (!isset($email) || !isset($password) || !isset($roles)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST, "Email, password, and roles must not be empty");
            return $response;
        };

        $hashedPassword = $passwordHasher->hashPassword(new User(), $password);
        $entityManager = $doctrine->getManager();
        $user = new User();
        $user->setEmail($email);
        $user->setRoles([$roles]);
        $user->setPassword($hashedPassword);

        $entityManager->persist($user);

        $entityManager->flush();

        $id = $user->getId();

        $response->setStatusCode(Response::HTTP_CREATED, "Created");
        $response->headers->set("Location", $urlHelper->getAbsoluteUrl('/api/user/' . $id));

        return $response;
    }

    /**
     * @Route("/users/latest", name="read_last_user", methods={"GET"})
     * @Route("/users/{id}", name="read_user", methods={"GET"})
     */
    public function read_user(ManagerRegistry $doctrine, $id = "", UrlHelper $urlHelper, Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $user = ($request->attributes->get('_route') == "read_last_user") ? $doctrine->getRepository(User::class)->findLastEntry() : $doctrine->getRepository(User::class)->find($id);

        if (!$user) {
            throw $this->createNotFoundException('No user found for ' . $id);
        }


        $email = $user->getEmail();
        $roles = $user->getRoles();

        $baseUrl = $urlHelper->getAbsoluteUrl('/users');

        $response->headers->set("Link", "<" . $baseUrl . "/users/" . $id . "/qrcode>; title=\"QR code\"; type=\"image/png\"");
        $response->headers->set("Link", "<" . $baseUrl . "/users>; rel=\"collection\"", false);
        $response->setCache([
            'last_modified' => $user->getLastupdate(),
            'etag' => sha1($response->getContent() . $id),
            'max_age' => 60,
            'public' => true
        ]);
        $response->isNotModified($request);

        $response->setVary("Accept");

        $response->setData([
            "email" => $email,
            "roles" => $roles
        ]);

        return $response;
    }

    /**
     * @Route("/users/{id}", name="update_user", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function update_user(Request $request, ManagerRegistry $doctrine, $id, UserPasswordHasherInterface $passwordHasher): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a page without having ROLE_ADMIN');
        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND, "Wrong user id!");
            return $response;
        }

        // Recuperation des elements de la requete
        $data = $doctrine->getRepository(User::class)->getJson($request); 
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $roles = $data['roles'] ?? null;
        
        // Si le name ou l’url sont vides
        if (!isset($email) and !isset($password) and !isset($roles)) {
            $response->setStatusCode(Response::HTTP_BAD_REQUEST, "Your request is empty!");
            return $response;
        }

        // on fait les modifs dans le modele doctrine
        if ($email) {
            $user->setEmail($email);
        }
        if ($password) {
            $hashedPassword = $passwordHasher->hashPassword(new User(), $password);
            $user->setPassword($hashedPassword);
        }
        if ($roles) {
            $user->setRoles($roles);
        }

        $entityManager = $doctrine->getManager();
        $entityManager->flush();

        // On fabrique la reponse
        $response->setStatusCode(Response::HTTP_OK, "Content updated");


        return $response;

    }

     /**
     * @Route("/users/{id}", name="delete_user", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete_user(Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $entityManager = $doctrine->getManager();
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND, "Wrong user id!");
            return $response;
        }

        $entityManager->remove($user);
        $entityManager->flush();

        // On fabrique la reponse
        $response->setStatusCode(Response::HTTP_NO_CONTENT, "No Content");

        return $response;
    }

}
