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

    #[\Override]
    public function getId()
    {
        return 1;
    }

    #[\Override]
    public function getContentVariants()
    {
        return [];
    }

    #[\Override]
    public function getTitles()
    {
        return new ArrayCollection();
    }

    #[\Override]
    public function isRewriteVariantTitle()
    {
        return true;
    }

    #[\Override]
    public function getWebCatalog()
    {
        return $this->webCatalog;
    }
}
