<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\ProductBundle\Entity\Product;

class ProductStub extends Product
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
