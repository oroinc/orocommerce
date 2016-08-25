<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

class StubProductImage extends ProductImage
{
    /**
     * @var File
     */
    private $image;

    /**
     * @return File
     */
    public function getImage()
    {
        return $this->image;
    }

    /**
     * @param File $image
     * @return ProductImage
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }
}
