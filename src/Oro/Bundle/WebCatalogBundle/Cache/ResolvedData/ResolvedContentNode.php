<?php

namespace Oro\Bundle\WebCatalogBundle\Cache\ResolvedData;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;

class ResolvedContentNode implements ContentNodeInterface
{
    /**
     * @var int
     */
    protected $id;

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var Collection
     */
    protected $titles;

    /**
     * @var ResolvedContentVariant
     */
    protected $resolvedContentVariant;

    /**
     * @var Collection
     */
    protected $childNodes;

    /**
     * @param int $id
     * @param string $identifier
     * @param Collection $titles
     * @param ResolvedContentVariant $resolvedContentVariant
     */
    public function __construct($id, $identifier, Collection $titles, ResolvedContentVariant $resolvedContentVariant)
    {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->titles = $titles;
        $this->resolvedContentVariant = $resolvedContentVariant;
        $this->childNodes = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @return Collection
     */
    public function getTitles()
    {
        return $this->titles;
    }

    /**
     * @return ResolvedContentVariant
     */
    public function getResolvedContentVariant()
    {
        return $this->resolvedContentVariant;
    }

    /**
     * @return Collection|ContentVariantInterface[]
     */
    public function getContentVariants()
    {
        return new ArrayCollection([$this->getResolvedContentVariant()]);
    }

    /**
     * @param ResolvedContentNode $childNode
     */
    public function addChildNode(ResolvedContentNode $childNode)
    {
        $this->childNodes->add($childNode);
    }

    /**
     * @return Collection
     */
    public function getChildNodes()
    {
        return $this->childNodes;
    }
}
