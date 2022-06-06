<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNamesProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Adds sanitized original filenames for product images if this configuration option is enabled.
 */
class ProductImageFileNamesProvider implements FileNamesProviderInterface
{
    private const PRODUCT_ORIGINAL_FILE_NAMES_ENABLED = 'oro_product.original_file_names_enabled';

    private FileNamesProviderInterface $innerProvider;

    private ConfigManager $configManager;

    private ?FeatureChecker $featureChecker = null;

    public function __construct(
        FileNamesProviderInterface $innerProvider,
        ConfigManager $configManager
    ) {
        $this->innerProvider = $innerProvider;
        $this->configManager = $configManager;
    }

    public function setFeatureChecker(FeatureChecker $featureChecker): self
    {
        $this->featureChecker = $featureChecker;

        return $this;
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
        $this->clearCacheState();
        try {
            $fileNames[] = $this->innerProvider->getFileNames($file);
        } finally {
            $this->configManager->set(self::PRODUCT_ORIGINAL_FILE_NAMES_ENABLED, $initialOptionValue);
            $this->clearCacheState();
        }

        return array_values(array_unique(array_merge(...$fileNames)));
    }

    private function isApplicable(File $file): bool
    {
        return
            $file->getParentEntityClass() === ProductImage::class
            && $file->getOriginalFilename();
    }

    private function clearCacheState(): void
    {
        if ($this->featureChecker instanceof FeatureChecker) {
            $this->featureChecker->resetCache();
        } else {
            $this->configManager->flush();
        }
    }
}
