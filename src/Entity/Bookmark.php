<?php

namespace App\Entity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BookmarkRepository;

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
    #[Assert\NotBlank(message:"Le nom est requis.")]
    #[Assert\Length(min: 3, max: 255)]
    private $name;

    /**
     * @ORM\Column(type="text")
     */ 
    #[Assert\Length(max: 10000)]

    private $description;

    /**
     * @ORM\Column(type="string", length=10000)
     */
    #[Assert\NotBlank(message:"L'URL est requise.")]
    #[Assert\Length(min: 3, max: 255)]
    #[Assert\Url]
    private $url;

    /**
     * @ORM\Column(type="datetime")
     */
    private $lastupdate;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="bookmarks")
     * @ORM\JoinColumn(nullable=true)
     */
    private $user;

    public function __construct() {
        $this->lastupdate = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getLastupdate(): ?\DateTimeInterface
    {
        return $this->lastupdate;
    }

    public function setLastupdate(\DateTimeInterface $lastupdate): self
    {
        $this->lastupdate = $lastupdate;

        return $this;
    }
    public function getUser()
    {
        return $this->user;
    }

    public function setUser($user)
    {
        $this->user = $user;

        return $this;
    }
}
