<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\AbstractHumanReadableFileNameProvider;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Uses a sanitized original filename for product images if `product_original_filenames` feature is enabled.
 */
class ProductImageFileNameProvider extends AbstractHumanReadableFileNameProvider
{
    public function __construct(FileNameProviderInterface $innerProvider, ConfigManager $configManager)
    {
        parent::__construct($innerProvider);
    }

    public function getFileName(File $file): string
    {
        return parent::getFileName($file);
    }

    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string
    {
        return parent::getFilteredImageName($file, $filterName, $format);
    }

    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string
    {
        return parent::getResizedImageName($file, $width, $height, $format);
    }

    protected function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename()
            && $this->isFeaturesEnabled();
    }
}
