<?php

namespace Oro\Bundle\WebCatalogBundle\Cache\ResolvedData;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

/**
 * Represents ContentNode with resolved child nodes as well as content variants
 */
class ResolvedContentNode implements ContentNodeInterface
{
    protected int $id;

    protected string $identifier;

    protected Collection $titles;

    protected ResolvedContentVariant $resolvedContentVariant;

    protected Collection $childNodes;

    protected bool $rewriteVariantTitle = true;

    protected int $priority = 0;

    public function __construct(
        int $id,
        string $identifier,
        int $priority,
        Collection $titles,
        ResolvedContentVariant $resolvedContentVariant,
        bool $rewriteVariantTitle = true
    ) {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->priority = $priority;
        $this->titles = $titles;
        $this->resolvedContentVariant = $resolvedContentVariant;
        $this->childNodes = new ArrayCollection();
        $this->rewriteVariantTitle = $rewriteVariantTitle;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getTitles(): Collection
    {
        return $this->titles;
    }

    public function getResolvedContentVariant(): ResolvedContentVariant
    {
        return $this->resolvedContentVariant;
    }

    public function getContentVariants(): ArrayCollection
    {
        return new ArrayCollection([$this->getResolvedContentVariant()]);
    }

    public function addChildNode(ResolvedContentNode $childNode): self
    {
        $this->childNodes->add($childNode);

        return $this;
    }

    public function getChildNodes(): Collection
    {
        return $this->childNodes;
    }

    public function setChildNodes(ArrayCollection $childNodes): self
    {
        $this->childNodes = $childNodes;

        return $this;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function setPriority(int $priority): self
    {
        $this->priority = $priority;

        return $this;
    }

    public function isRewriteVariantTitle(): bool
    {
        return $this->rewriteVariantTitle;
    }
}
