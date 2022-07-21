<?php

namespace App\Entity;

use App\Repository\NodeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: NodeRepository::class)]
class Node
{
    #[ORM\Id]
    #[ORM\GeneratedValue('CUSTOM')]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\CustomIdGenerator(class: 'doctrine.uuid_generator')]
    #[Groups(['node'])]
    private $id;

    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'childNodes')]
    private $childNodes;

    public function __construct()
    {
        $this->childNodes = new ArrayCollection();
    }

    public function getId(): ?Uuid
    {
        return $this->id;
    }

    public function getChildNodes(): ?Collection
    {
        return $this->childNodes;
    }

    #[Groups(['node'])]
    public function getChildNodeIds(): array
    {
        return array_map(
            static fn ($node) => $node->getId(),
            iterator_to_array($this->childNodes)
        );
    }

    public function hasChildren(): bool
    {
        return count($this->childNodes) > 0;
    }

    public function setChildNodes(?self $childNodes): self
    {
        $this->childNodes = $childNodes;

        return $this;
    }

    public function addChildNode(self $childNode): self
    {
        if (!$this->childNodes->contains($childNode)) {
            $this->childNodes[] = $childNode;
        }

        return $this;
    }

    public function removeChildNode(self $childNode): self
    {
        $this->childNodes->removeElement($childNode);

        return $this;
    }
}
