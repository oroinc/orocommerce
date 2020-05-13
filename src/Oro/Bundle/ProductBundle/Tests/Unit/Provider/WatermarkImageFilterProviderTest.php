<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\WatermarkImageFilterProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;

class WatermarkImageFilterProviderTest extends \PHPUnit\Framework\TestCase
{
    const IMAGE_ID = 1;
    const SIZE = 50;
    const POSITION = 'center';
    const FILENAME = 'file.jpg';
    const ATTACHMENT_DIR = 'attachment';

    /** @var WatermarkImageFilterProvider */
    protected $provider;

    /** @var ConfigManager|Stub */
    protected $configManager;

    /** @var DoctrineHelper|MockObject */
    protected $doctrineHelper;

    protected function setUp(): void
    {
        $this->configManager = $this->createStub(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);

        $this->provider = new WatermarkImageFilterProvider(
            $this->configManager,
            $this->doctrineHelper,
            self::ATTACHMENT_DIR
        );
    }

    public function testGetFilterConfig()
    {
        $this->configManager
            ->method('get')
            ->willReturnMap([
                [
                    Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_FILE,
                    false,
                    false,
                    null,
                    self::IMAGE_ID
                ],
                [
                    Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_SIZE,
                    false,
                    false,
                    null,
                    self::SIZE
                ],
                [
                    Configuration::ROOT_NODE . '.' . Configuration::PRODUCT_IMAGE_WATERMARK_POSITION,
                    false,
                    false,
                    null,
                    self::POSITION
                ]
            ]);

        $image = new File();
        $image->setFilename(self::FILENAME);

        $repo = $this->createMock(EntityRepository::class);
        $repo->expects(static::once())->method('find')->with(self::IMAGE_ID)->willReturn($image);

        $this->doctrineHelper->expects(static::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        $expectedConfig = [
            'filters' => [
                'watermark' => [
                    'image' => self::ATTACHMENT_DIR . '/' . self::FILENAME,
                    'size' => round(self::SIZE/ 100, 2),
                    'position' => self::POSITION
                ]
            ]
        ];

        static::assertEquals($expectedConfig, $this->provider->getFilterConfig());
    }

    public function testGetFilterConfigNoValue()
    {
        $this->configManager->method('get')->willReturn(null);
        $this->doctrineHelper->expects(static::never())->method('getEntityRepositoryForClass');

        static::assertEquals([], $this->provider->getFilterConfig());
    }

    public function testIsApplicable()
    {
        $dimension = $this->createMock(ThemeImageTypeDimension::class);

        $dimension->expects(static::exactly(2))
            ->method('hasOption')
            ->with(WatermarkImageFilterProvider::APPLY_PRODUCT_IMAGE_WATERMARK_OPTION_NAME)
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $dimension->expects(static::once())
            ->method('getOption')
            ->with(WatermarkImageFilterProvider::APPLY_PRODUCT_IMAGE_WATERMARK_OPTION_NAME)
            ->willReturn(true);

        static::assertTrue($this->provider->isApplicable($dimension));
        static::assertFalse($this->provider->isApplicable($dimension));
    }
}
