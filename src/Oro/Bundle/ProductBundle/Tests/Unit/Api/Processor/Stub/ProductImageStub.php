<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub;

use Oro\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageStub extends ProductImage
{
    public function getImage()
    {
        return null;
    }
}
