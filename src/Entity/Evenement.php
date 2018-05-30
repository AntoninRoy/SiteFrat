<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EvenementRepository")
 */
class Evenement
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Post", mappedBy="evenement")
     */
    private $Posts;

    public function __construct()
    {
        $this->Posts = new ArrayCollection();
    }

    public function getId()
    {
        return $this->id;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
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

    /**
     * @return Collection|Post[]
     */
    public function getPosts(): Collection
    {
        return $this->Posts;
    }

    public function addPost(Post $post): self
    {
        if (!$this->Posts->contains($post)) {
            $this->Posts[] = $post;
            $post->setEvenement($this);
        }

        return $this;
    }

    public function removePost(Post $post): self
    {
        if ($this->Posts->contains($post)) {
            $this->Posts->removeElement($post);
            // set the owning side to null (unless already changed)
            if ($post->getEvenement() === $this) {
                $post->setEvenement(null);
            }
        }

        return $this;
    }
}
