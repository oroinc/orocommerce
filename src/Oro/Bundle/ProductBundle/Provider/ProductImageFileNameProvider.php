<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameExtensionHelper;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Uses a sanitized original filename for product images if this configuration option is enabled.
 */
class ProductImageFileNameProvider implements FileNameProviderInterface
{
    private const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';
    private const ORIGINAL_FILE_NAME_SEPARATOR = '-';

    private FileNameProviderInterface $innerProvider;

    private ConfigManager $configManager;

    public function __construct(FileNameProviderInterface $innerProvider, ConfigManager $configManager)
    {
        $this->innerProvider = $innerProvider;
        $this->configManager = $configManager;
    }

    public function getFileName(File $file): string
    {
        return $this->innerProvider->getFileName($file);
    }

    public function getFilteredImageName(File $file, string $filterName, string $format = ''): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFilteredImageName($file, $filterName, $format);
        }

        return $this->getNameWithFormat($file, $format);
    }

    public function getResizedImageName(File $file, int $width, int $height, string $format = ''): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getResizedImageName($file, $width, $height, $format);
        }

        return $this->getNameWithFormat($file, $format);
    }

    private function getNameWithFormat(File $file, string $format): string
    {
        $hash = str_replace('.' . $file->getExtension(), '', $file->getFilename());
        $filename = $hash . self::ORIGINAL_FILE_NAME_SEPARATOR . $file->getOriginalFilename();
        $filename = FilenameExtensionHelper::addExtension($filename, $format);

        return FilenameSanitizer::sanitizeFilename($filename);
    }

    private function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename()
            && $this->configManager->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
    }
}
