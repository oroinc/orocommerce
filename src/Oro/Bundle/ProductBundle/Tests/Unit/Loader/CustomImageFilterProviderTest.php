<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Loader;

use Doctrine\ORM\EntityRepository;

use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\CustomImageFilterProvider;

class CustomImageFilterProviderTest extends \PHPUnit_Framework_TestCase
{
    const IMAGE_ID = 1;
    const SIZE = 50;
    const POSITION = 'center';
    const FILENAME = 'file.jpg';

    /**
     * @var CustomImageFilterProvider
     */
    protected $provider;

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    public function setUp()
    {
        $this->configManager = $this->prophesize(ConfigManager::class);
        $this->doctrineHelper = $this->prophesize(DoctrineHelper::class);

        $this->provider = new CustomImageFilterProvider(
            $this->configManager->reveal(),
            $this->doctrineHelper->reveal()
        );
    }

    public function testGetFilterConfig()
    {
        list($fileConfigKey, $sizeConfigKey, $positionConfigKey) = $this->prepareKeys();

        $this->configManager->get($fileConfigKey)->willReturn(self::IMAGE_ID);
        $this->configManager->get($sizeConfigKey)->willReturn(self::SIZE);
        $this->configManager->get($positionConfigKey)->willReturn(self::POSITION);

        $image = new File();
        $image->setFilename(self::FILENAME);

        $repo = $this->prophesize(EntityRepository::class);
        $repo->find(self::IMAGE_ID)->willReturn($image);

        $this->doctrineHelper->getEntityRepositoryForClass(File::class)->willReturn($repo->reveal());

        $expectedConfig = [
            'filters' => [
                'watermark' => [
                    'image' => 'attachment/' . self::FILENAME,
                    'size' => round(self::SIZE/ 100, 2),
                    'position' => self::POSITION
                ]
            ]
        ];

        $this->assertEquals($expectedConfig, $this->provider->getFilterConfig());
    }

    public function testGetFilterConfigNoValue()
    {
        list($fileConfigKey, $sizeConfigKey, $positionConfigKey) = $this->prepareKeys();

        $this->configManager->get($fileConfigKey)->willReturn(null);
        $this->configManager->get($sizeConfigKey)->willReturn(null);
        $this->configManager->get($positionConfigKey)->willReturn(null);

        $this->assertEquals([], $this->provider->getFilterConfig());
    }

    /**
     * @return array
     */
    protected function prepareKeys()
    {
        $fileConfigKey = Configuration::ROOT_NODE.'.'.Configuration::PRODUCT_IMAGE_WATERMARK_FILE;
        $sizeConfigKey = Configuration::ROOT_NODE.'.'.Configuration::PRODUCT_IMAGE_WATERMARK_SIZE;
        $positionConfigKey = Configuration::ROOT_NODE.'.'.Configuration::PRODUCT_IMAGE_WATERMARK_POSITION;

        return [$fileConfigKey, $sizeConfigKey, $positionConfigKey];
    }
}
