<?php

namespace App\Controller;

use App\Form\BookmarkFormType;
use App\Service\Metadata\Crawler\MetadataCrawlerInterface;
use App\Service\Metadata\Parser\MetadataParserInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\UrlHelper;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use App\Entity\Bookmark;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

class BookmarkController extends AbstractController
{

    use GetRequestData;
    /**
     * @Route("/bookmark/create", name="create_bookmark", methods={"POST"})
     */

    public function create_bookmark(ValidatorInterface $validator, ManagerRegistry $doctrine, Request $request, UrlHelper $urlHelper): Response
    {

        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        $requestData = $this->getRequestData($request);

        $entityManager = $doctrine->getManager();

        $bookmark = new Bookmark();
        $form = $this->create_form($bookmark);

        $form->submit($requestData);

        if (!$form->isValid()) {
            $errors = $form->getErrors();
            $errorsString = (string) $errors;
            dd($errors);
            return new JsonResponse($errorsString, Response::HTTP_BAD_REQUEST);
        }


        $bookmark = $form->getData();

        $entityManager->persist($bookmark);

        $entityManager->flush();

        $response->setStatusCode(Response::HTTP_CREATED, "Created");
        // $response->headers->set("Location", $urlHelper->getAbsoluteUrl('/api/bookmarks/' . $id));

        return $response;
    }

    /**
     * @Route("/api/bookmarks/latest", name="read_last_bookmark", methods={"GET"})
     * @Route("api/bookmarks/{id}", name="read_bookmark", methods={"GET"})
     */
    public function read_bookmark(ManagerRegistry $doctrine, $id = "", UrlHelper $urlHelper, Request $request): JsonResponse
    {
        $response = new JsonResponse();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        /** @var Bookmark $bookmark */
        $bookmark = ($request->attributes->get('_route') == "read_last_bookmark") ? $doctrine->getRepository(Bookmark::class)->findLastEntry() : $doctrine->getRepository(Bookmark::class)->find($id);

        if (!$bookmark) {
            throw $this->createNotFoundException('No bookmark found for ' . $id);
        }

        $name = $bookmark->getName();
        $desc = $bookmark->getDescription();
        $url = $bookmark->getUrl();
        $id = $bookmark->getId();
        $userBookmark = $bookmark->getUser();
        $user = $this->getUser();
        $userRole = $user->getRoles();
        if ($userBookmark) {
            if ($user === $userBookmark) {
                $baseUrl = $urlHelper->getAbsoluteUrl('/api/bookmarks');

                // Fabrication des differentes entrees Link de la reponse HTTP
                $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks/" . $id . "/qrcode>; title=\"QR code\"; type=\"image/png\"");
                // Encore un header Link, donc avec l'attribut false pour éviter l'écrasement
                $response->headers->set("Link", "<" . $url . ">; rel=\"related\"; title=\"Bookmarked link\"", false);
                $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks>; rel=\"collection\"", false);
                $response->setCache([
                    'last_modified' => $bookmark->getLastupdate(),
                    'etag' => sha1($response->getContent() . $id),
                    'max_age' => 60,
                    'public' => true
                ]);
                $response->isNotModified($request);

                $response->setVary("Accept");

                $response->setData([
                    "name" => $name,
                    "url" => $url,
                    "description" => $desc
                ]);
            } else if (in_array($userRole, "ROLE_ADMIN")) {
                $baseUrl = $urlHelper->getAbsoluteUrl('/api/bookmarks');

                // Fabrication des differentes entrees Link de la reponse HTTP
                $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks/" . $id . "/qrcode>; title=\"QR code\"; type=\"image/png\"");
                // Encore un header Link, donc avec l'attribut false pour éviter l'écrasement
                $response->headers->set("Link", "<" . $url . ">; rel=\"related\"; title=\"Bookmarked link\"", false);
                $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks>; rel=\"collection\"", false);
                $response->setCache([
                    'last_modified' => $bookmark->getLastupdate(),
                    'etag' => sha1($response->getContent() . $id),
                    'max_age' => 60,
                    'public' => true
                ]);
                $response->isNotModified($request);

                $response->setVary("Accept");

                $response->setData([
                    "name" => $name,
                    "url" => $url,
                    "description" => $desc
                ]);
            } else {
                return $this->json([
                    'message' => 'Vous n\'avez pas les droits pour accéder à ce bookmark.'
                ], JsonResponse::HTTP_UNAUTHORIZED);
            }
        } else {
            $baseUrl = $urlHelper->getAbsoluteUrl('/api/bookmarks');

            // Fabrication des differentes entrees Link de la reponse HTTP
            $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks/" . $id . "/qrcode>; title=\"QR code\"; type=\"image/png\"");
            // Encore un header Link, donc avec l'attribut false pour éviter l'écrasement
            $response->headers->set("Link", "<" . $url . ">; rel=\"related\"; title=\"Bookmarked link\"", false);
            $response->headers->set("Link", "<" . $baseUrl . "/api/bookmarks>; rel=\"collection\"", false);
            $response->setCache([
                'last_modified' => $bookmark->getLastupdate(),
                'etag' => sha1($response->getContent() . $id),
                'max_age' => 60,
                'public' => true
            ]);
            $response->isNotModified($request);

            $response->setVary("Accept");

            $response->setData([
                "name" => $name,
                "url" => $url,
                "description" => $desc
            ]);
        }



        return $response;
    }

    /**
     * @Route("/api/bookmarks", name="read_collection", methods={"GET"}, priority=2)
     * @Route("/api/bookmarks/pages/{!page}", name="read_collection_page", methods={"GET"}, requirements={"page"="\d+"}, defaults={"page":1})
     * @Route("/api/bookmarks/pages/{page}/{step}", name="read_collection_page_step", methods={"GET"}, requirements={"page"="\d+", "step"="\d+"}, defaults={"page":1})
     * @Route("/api/bookmarks/last", name="read_collection_last_page", methods={"GET"})
     * @Route("/api/bookmarks/last/{step}", name="read_collection_last_page_step", methods={"GET"}, requirements={"step"="\d+"})
     */
    public function read_bookmark_collection(?int $page = 1, ?int $step = 10, ManagerRegistry $doctrine, UrlHelper $urlHelper, Request $request): JsonResponse
    {
        // On ne fixe pas le pas $step et l'occurence $current de la pagination
        //$step = 10;
        $nbtotal = $doctrine->getRepository(Bookmark::class)->countAll();
        $current = (in_array($request->attributes->get('_route'), ["read_collection_last_page", "read_collection_last_page_step"])) ? max(1, $nbtotal - $step) : ($page - 1) * $step + 1;
        $current = max($current, 1);
        $step = max($step, 10);

        // creation de l'objet Response
        $response = new jsonResponse();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        # L'URL de base pour les links
        $baseurl = $urlHelper->getAbsoluteUrl($this->generateUrl('read_collection'));
        $response->headers->set("Link", "<" . $baseurl . "/last/>; rel=\"last\"");
        $response->headers->set("Link", "<" . $baseurl . ">; rel=\"first\"", false);
        $response->headers->set("X-Total-Count", $nbtotal);
        $response->headers->set("X-Current-Page", intdiv($current, $step) + 1);
        $response->setCache([
            'etag' => sha1($response->getContent() . $response->headers->get("X-Total-Count") . $response->headers->get("X-Current-page") . $response->headers->get("X-Page-Size")),
            'max_age' => 60,
            'public' => true
        ]);
        $response->isNotModified($request);

        // On recupere une liste de $step bookmarks
        $bookmarks = $doctrine->getRepository(Bookmark::class)->findNextX($current - 1, $step);

        // Pour chacun on construit l'array de data
        $json_data = [];

        // On renmplit la réponse JSON avec les liens vers les bookmarks
        foreach ($bookmarks as $bookmark) {
            $json_data['Locations'][] = $baseurl . '/' . $bookmark->getId();
        }
        $response->headers->set("X-Page-Size", count($bookmarks));

        $json_data['meta'] = ["total_count" => count($bookmarks)]; // pour le contenu meta du JSON

        if ($current > $nbtotal) {

            $response->setStatusCode(Response::HTTP_NO_CONTENT, "Max. page number reached");
            return $response;
        }

        if (!in_array($request->attributes->get('_route'), ["read_collection_last_page", "read_collection_last_page_step"])) {
            $nextpage = intdiv($current, $step) + 2;
            $response->headers->set("Link", "<" . $baseurl . "/pages/" . ">" . $nextpage . "; rel=\"next\"", false);
        }

        $nextXbookmarks = ($current + $step <= $nbtotal) ? $current + $step : $nbtotal;
        $response->headers->set("Content-range", "urls " . $current . "-" . $nextXbookmarks . "/" . $nbtotal);


        // fin de la preparation de la reponse : inclusion du JSON et du status
        $response->setData($json_data);

        $response->setStatusCode(Response::HTTP_PARTIAL_CONTENT);



        return $response;

    }

    /**
     * @Route("/api/bookmarks/{id}", name="update_bookmark", methods={"PUT"}, requirements={"id"="\d+"})
     */
    public function update_bookmark(ValidatorInterface $validator, Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN', null, 'User tried to access a page without having ROLE_ADMIN');
        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        // Requete manuelle pour recuperer l’objet Bookmark
        $entityManager = $doctrine->getManager();
        $bookmark = $entityManager->getRepository(Bookmark::class)->find($id);

        if (!$bookmark) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND, "Bookmark not founded");
            return $response;
        }

        $form = $this->createForm(BookmarkFormType::class, ['csrf_protection' => false]);
        $form->submit($this->getRequestData($request));

        if ($form->isSubmitted && $form->isValid) {
            $entityManager = $doctrine->getManager();
            $entityManager->flush();
            $response->setStatusCode(Response::HTTP_OK, "Content updated");
            return $response;
        }

        $errorMessage = [];
        foreach ($form->getErrors(true) as $error) {
            $errorMessage = $error->getMessage();
        }

        return $this->json($errorMessage, Response::HTTP_BAD_REQUEST);

    }

    /**
     * @Route("/api/bookmarks/{id}", name="delete_bookmark", methods={"DELETE"}, requirements={"id"="\d+"})
     */
    public function delete_bookmark(Request $request, ManagerRegistry $doctrine, $id): Response
    {
        $response = new Response();
        $response->headers->set('Server', 'ExoAPICRUDREST');

        // Requete manuelle pour recuperer l’objet Bookmark
        $entityManager = $doctrine->getManager();
        $bookmark = $entityManager->getRepository(Bookmark::class)->find($id);

        if (!$bookmark) {
            $response->setStatusCode(Response::HTTP_NOT_FOUND, "Wrong bookmark id!");
            return $response;
        }

        $entityManager->remove($bookmark);
        $entityManager->flush();

        // On fabrique la reponse
        $response->setStatusCode(Response::HTTP_NO_CONTENT, "No Content");

        return $response;
    }
    private function create_form(Bookmark $bookmark)
    {

        $form = $this->createFormBuilder($bookmark, ['csrf_protection' => false])
            ->add('name')
            ->add('description')
            ->add('url')
            ->getForm();

        return $form;
    }

    /**
     * @Route("/api/bookmarks/latest/metadata", name="read_last_bookmark_metadata", methods={"GET"})
     * @Route("api/bookmarks/{id}/metadata", name="read_bookmark_metadata", methods={"GET"})
     */
    public function metadata(
        ManagerRegistry $doctrine,
        $id = "",
        Request $request,
        #[Autowire(service: 'metadataCurl')] MetadataCrawlerInterface $metadataCrawler,
        MetadataParserInterface $metadataParser,
        CacheInterface $cache,
        LoggerInterface $loggerInterface
    ): JsonResponse {

        /** @var Bookmark $bookmark */
        $bookmark = ($request->attributes->get('_route') == "read_last_bookmark") ? $doctrine->getRepository(Bookmark::class)->findLastEntry() : $doctrine->getRepository(Bookmark::class)->find($id);

        if (!$bookmark) {
            throw $this->createNotFoundException('No bookmark found for ' . $id);
        }

        $url = $bookmark->getUrl();
        $metadata = $cache->get("bookmark_" . $id, function (ItemInterface $item) use ($metadataParser, $url, $metadataCrawler, $loggerInterface): array {
            $item->expiresAfter(3600);
            $content = $metadataCrawler->getContent($url);
            $loggerInterface->debug("Metadata with" . $item->getKey() . "URL : " . $url);
            return $metadataParser->getMetadata($url, $content["content"]);
        });
        return new JsonResponse($metadata);

    }
}
