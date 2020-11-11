<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Api\Processor\Stub;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

class ProductImageStub extends ProductImage
{
    /** @var File|null */
    protected $image;

    /**
     * @return File|null
     */
    public function getImage(): ?File
    {
        return $this->image;
    }

    /**
     * @param File|null $image
     */
    public function setImage(?File $image): void
    {
        $this->image = $image;
    }
}
