<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Provider\FileNameProviderInterface;
use Oro\Bundle\AttachmentBundle\Tools\FilenameSanitizer;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\ProductImage;

/**
 * Use sanitized original filename for product images if enabled.
 */
class ProductImageFileNameProvider implements FileNameProviderInterface
{
    private const SEPARATOR = '-';

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var FileNameProviderInterface
     */
    private $innerProvider;

    /**
     * @param ConfigManager $configManager
     * @param FileNameProviderInterface $innerProvider
     */
    public function __construct(
        ConfigManager $configManager,
        FileNameProviderInterface $innerProvider
    ) {
        $this->configManager = $configManager;
        $this->innerProvider = $innerProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getFileName(File $file): string
    {
        if ($file->getParentEntityClass() === ProductImage::class
            && $this->configManager->get('oro_product.original_file_names_enabled')
        ) {
            $hash = str_replace('.' . $file->getExtension(), '', $file->getFilename());

            return FilenameSanitizer::sanitizeFilename($hash . self::SEPARATOR . $file->getOriginalFilename());
        }

        return $this->innerProvider->getFileName($file);
    }
}
