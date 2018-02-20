<?php

namespace Oro\Bundle\WebCatalogBundle\Cache\ResolvedData;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;

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
     * @var bool
     */
    protected $rewriteVariantTitle = true;

    /**
     * @param int $id
     * @param string $identifier
     * @param Collection $titles
     * @param ResolvedContentVariant $resolvedContentVariant
     * @param bool $rewriteVariantTitle
     */
    public function __construct(
        $id,
        $identifier,
        Collection $titles,
        ResolvedContentVariant $resolvedContentVariant,
        $rewriteVariantTitle = true
    ) {
        $this->id = $id;
        $this->identifier = $identifier;
        $this->titles = $titles;
        $this->resolvedContentVariant = $resolvedContentVariant;
        $this->childNodes = new ArrayCollection();
        $this->rewriteVariantTitle = $rewriteVariantTitle;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
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
     * {@inheritdoc}
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

    /**
     * {@inheritdoc}
     */
    public function isRewriteVariantTitle()
    {
        return $this->rewriteVariantTitle;
    }
}
