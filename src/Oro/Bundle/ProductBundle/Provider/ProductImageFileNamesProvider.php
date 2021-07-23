<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Adds sanitized original filenames for product images if this configuration option is enabled.
 */
class ProductImageFileNamesProvider implements FileNamesProviderInterface
{
    private const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';

    /** @var FileNamesProviderInterface */
    private $innerProvider;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        FileNamesProviderInterface $innerProvider,
        ConfigManager $configManager
    ) {
        $this->innerProvider = $innerProvider;
        $this->configManager = $configManager;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileNames(File $file): array
    {
        if (!$this->isApplicable($file)) {
            return $this->innerProvider->getFileNames($file);
        }

        $fileNames = [$this->innerProvider->getFileNames($file)];
        $initialOptionValue = $this->configManager->get(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED);
        $this->configManager->set(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, !$initialOptionValue);
        try {
            $fileNames[] = $this->innerProvider->getFileNames($file);
        } finally {
            $this->configManager->set(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, $initialOptionValue);
        }

        return array_values(array_unique(array_merge(...$fileNames)));
    }

    private function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename();
    }
}
