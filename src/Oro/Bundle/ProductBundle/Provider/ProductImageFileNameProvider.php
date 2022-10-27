<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\AbstractHumanReadableFileNameProvider;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Uses a sanitized original filename for product images if `product_original_filenames` feature is enabled.
 */
class ProductImageFileNameProvider extends AbstractHumanReadableFileNameProvider
{
    protected function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename()
            && $this->isFeaturesEnabled();
    }
}
