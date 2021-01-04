<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\FileManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Provider\WatermarkImageFilterProvider;

class WatermarkImageFilterProviderTest extends \PHPUnit\Framework\TestCase
{
    private const IMAGE_ID = 1;
    private const SIZE = 50;
    private const POSITION = 'center';
    private const FILENAME = 'file.jpg';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var FileManager|\PHPUnit\Framework\MockObject\MockObject */
    private $fileManager;

    /** @var WatermarkImageFilterProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->fileManager = $this->createMock(FileManager::class);

        $this->provider = new WatermarkImageFilterProvider(
            $this->configManager,
            $this->doctrineHelper,
            $this->fileManager
        );
    }

    public function testGetFilterConfig()
    {
        $this->configManager->expects(self::any())
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
        $repo->expects(self::once())
            ->method('find')
            ->with(self::IMAGE_ID)
            ->willReturn($image);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepositoryForClass')
            ->with(File::class)
            ->willReturn($repo);

        $expectedImagePath = 'gaufrette://attachments/attachments/' . self::FILENAME;
        $this->fileManager->expects(self::once())
            ->method('getFilePathWithoutProtocol')
            ->with(self::FILENAME)
            ->willReturn($expectedImagePath);

        $expectedConfig = [
            'filters' => [
                'watermark' => [
                    'image' => $expectedImagePath,
                    'size' => round(self::SIZE/ 100, 2),
                    'position' => self::POSITION
                ]
            ]
        ];

        self::assertEquals($expectedConfig, $this->provider->getFilterConfig());
    }

    public function testGetFilterConfigNoValue()
    {
        $this->configManager->expects(self::any())
            ->method('get')
            ->willReturn(null);
        $this->doctrineHelper->expects(self::never())
            ->method('getEntityRepositoryForClass');

        self::assertEquals([], $this->provider->getFilterConfig());
    }

    public function testIsApplicable()
    {
        $dimension = $this->createMock(ThemeImageTypeDimension::class);

        $dimension->expects(self::exactly(2))
            ->method('hasOption')
            ->with(WatermarkImageFilterProvider::APPLY_PRODUCT_IMAGE_WATERMARK_OPTION_NAME)
            ->willReturnOnConsecutiveCalls(
                true,
                false
            );
        $dimension->expects(self::once())
            ->method('getOption')
            ->with(WatermarkImageFilterProvider::APPLY_PRODUCT_IMAGE_WATERMARK_OPTION_NAME)
            ->willReturn(true);

        self::assertTrue($this->provider->isApplicable($dimension));
        self::assertFalse($this->provider->isApplicable($dimension));
    }
}
