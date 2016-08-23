<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Provider\CustomImageFilterProviderInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

class CustomImageFilterProvider implements CustomImageFilterProviderInterface
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param ConfigManager $configManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(ConfigManager $configManager, DoctrineHelper $doctrineHelper)
    {
        $this->configManager = $configManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterConfig()
    {
        $config = [];
        $fileConfigKey = Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_FILE;
        $sizeConfigKey = Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_SIZE;
        $positionConfigKey = Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_POSITION;

        $imageId = $this->configManager->get($fileConfigKey);
        $size = $this->configManager->get($sizeConfigKey);
        $position = $this->configManager->get($positionConfigKey);

        if ($imageId) {
            /** @var File $image */
            $image = $this->doctrineHelper->getEntityRepositoryForClass(File::class)->find($imageId);
            $filePath = 'attachment/' . $image->getFilename();

            $config = [
                'filters' => [
                    'watermark' => [
                        'image' => $filePath,
                        'size' => round($size / 100, 2),
                        'position' => $position
                    ]
                ]
            ];
        }

        return $config;
    }
}
