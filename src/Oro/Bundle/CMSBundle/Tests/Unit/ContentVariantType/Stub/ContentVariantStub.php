<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\ContentVariantType\Stub;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Component\WebCatalog\Test\Unit\Form\Type\AbstractContentVariantStub;

class ContentVariantStub extends AbstractContentVariantStub
{
    /**
     * @var Page
     */
    protected $cmsPage;

    /**
     * @return Page
     */
    public function getCmsPage()
    {
        return $this->cmsPage;
    }

    /**
     * @param Page $cmsPage
     * @return ContentVariantStub
     */
    public function setCmsPage($cmsPage)
    {
        $this->cmsPage = $cmsPage;

        return $this;
    }
}
