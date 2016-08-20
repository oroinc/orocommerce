<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CMSBundle\Entity\Page;

class PageStub extends Page
{
    use MetaFieldSetterGetterTrait {
        MetaFieldSetterGetterTrait::__construct as private traitConstructor;
    }

    public function __construct()
    {
        parent::__construct();
        $this->traitConstructor();
    }
}
