<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

class ContentNodeStub implements ContentNodeInterface, WebCatalogAwareInterface
{
    /**
     * @var int
     */
    private $id;

    /**
     * @var ContentVariantInterface
     */
    private $contentVariants;

    /**
     * @var WebCatalogInterface
     */
    private $webCatalog;

    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentVariants()
    {
        return $this->contentVariants;
    }

    /**
     * @param ContentVariantInterface $contentVariant
     *
     * @return ContentNodeStub
     */
    public function addContentVariant(ContentVariantInterface $contentVariant)
    {
        $this->contentVariants[] = $contentVariant;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getTitles()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function isRewriteVariantTitle()
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebCatalog()
    {
        return $this->webCatalog;
    }

    /**
     * @param WebCatalogInterface $webCatalog
     *
     * @return ContentNodeStub
     */
    public function setWebCatalog(WebCatalogInterface $webCatalog)
    {
        $this->webCatalog = $webCatalog;

        return $this;
    }
}
