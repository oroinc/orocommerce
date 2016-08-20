<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\CatalogBundle\Entity\Category;

class CategoryStub extends Category
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
