<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Uses a sanitized original filename for product images if this configuration option is enabled.
 */
class ProductImageFileNameProvider implements FileNameProviderInterface
{
    private const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';
    private const ORIGINAL_FILE_NAME_SEPARATOR        = '-';

    /** @var FileNameProviderInterface */
    private $innerProvider;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        FileNameProviderInterface $innerProvider,
        ConfigManager $configManager
    ) {
        $this->innerProvider = $innerProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName(File $file): string
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFileName($file);
        }

        $hash = str_replace('.' . $file->getExtension(), '', $file->getFilename());

        return FilenameSanitizer::sanitizeFilename(
            $hash . self::ORIGINAL_FILE_NAME_SEPARATOR . $file->getOriginalFilename()
        );
    }

    private function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename()
            && $this->configManager->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
    }
}
