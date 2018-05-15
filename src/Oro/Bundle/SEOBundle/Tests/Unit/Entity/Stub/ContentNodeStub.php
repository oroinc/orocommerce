<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Component\WebCatalog\Entity\ContentNodeInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogAwareInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;

class ContentNodeStub implements ContentNodeInterface, WebCatalogAwareInterface
{
    use MetaFieldSetterGetterTrait {
        MetaFieldSetterGetterTrait::__construct as private traitConstructor;
    }

    /**
     * @var WebCatalogInterface
     */
    private $webCatalog;

    public function __construct()
    {
        $this->traitConstructor();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return 1;
    }

    /**
     * {@inheritdoc}
     */
    public function getContentVariants()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getTitles()
    {
        return new ArrayCollection();
    }

    /**
     * {@inheritdoc}
     */
    public function isRewriteVariantTitle()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getWebCatalog()
    {
        return $this->webCatalog;
    }
}
