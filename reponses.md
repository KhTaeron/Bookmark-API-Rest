# Question 1:

Voici l'entité : 

```
<?php

namespace App\Entity;

use App\Repository\BookmarkRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=BookmarkRepository::class)
 */
class Bookmark
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }
}

```

L'entité bookmark a comme propriétés : l'id ( généré automatiquement ), le nom ( name ), sa description et l'url qui correspond. 

## Question 2 :

Voici la méthode de création d'un bookmark : 

```
    /**
     * @Route("/bookmark/create", name="create_bookmark", methods={"POST"})
     */

     public function create_bookmark(ManagerRegistry $doctrine, Request $request): Response
     {
         $entityManager = $doctrine->getManager();
         $name = $request->get("name");
         $description = $request->get("description");
         $url= $request->get("url");

         $bookmark = new Bookmark();
         $bookmark->setName((string)$name);
         $bookmark->setDescription((string)$description);
         $bookmark->setUrl((string)$url);
 
         $entityManager->persist($bookmark);
 
         $entityManager->flush();
 
         return new Response('Saved new bookmark with id ' . $bookmark->getId());
     }
```