<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Helper;

use Oro\Bundle\ProductBundle\Helper\ProductCollectionSegmentHelper;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\ContentNodeStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\WebCatalog\Entity\ContentVariantInterface;
use Oro\Component\WebCatalog\Entity\WebCatalogInterface;
use Oro\Component\WebCatalog\Provider\WebCatalogUsageProviderInterface;

class ProductCollectionSegmentHelperTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const SEGMENT_ID = 123;
    private const FIRST_WEB_CATALOG_ID = 111;
    private const SECOND_WEB_CATALOG_ID = 777;
    private const THIRD_WEB_CATALOG_ID = 999;
    private const FIRST_WEBSITE_ID = 1;
    private const SECOND_WEBSITE_ID = 2;

    /** @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $contentVariantSegmentProvider;

    /** @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $webCatalogUsageProvider;

    /** @var ProductCollectionSegmentHelper */
    private $helper;

    protected function setUp(): void
    {
        $this->contentVariantSegmentProvider = $this->createMock(ContentVariantSegmentProvider::class);
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);

        $this->helper = new ProductCollectionSegmentHelper(
            $this->contentVariantSegmentProvider,
            $this->webCatalogUsageProvider
        );
    }

    public function testGetWebsiteIdsBySegmentWhenWebCatalogBundleIsDisabled()
    {
        $this->helper = new ProductCollectionSegmentHelper($this->contentVariantSegmentProvider);

        $this->contentVariantSegmentProvider->expects($this->never())
            ->method('getContentVariants');

        $this->assertEmpty($this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    public function testGetWebsiteIdsBySegmentWhenNoContentVariantsExist()
    {
        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('getContentVariants')
            ->willReturn([]);

        $this->assertEmpty($this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    /**
     * @dataProvider webCatalogsAssignmentDataProvider
     */
    public function testGetWebsiteIdsBySegment(array $webCatalogAssignment, array $expectedWebsiteIds)
    {
        $firstContentVariant = $this->getContentVariant(self::FIRST_WEB_CATALOG_ID);
        $secondContentVariant = $this->getContentVariant(self::SECOND_WEB_CATALOG_ID);

        $this->contentVariantSegmentProvider->expects($this->once())
            ->method('getContentVariants')
            ->willReturn([$firstContentVariant, $secondContentVariant]);

        $this->webCatalogUsageProvider->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn($webCatalogAssignment);

        $this->assertEquals($expectedWebsiteIds, $this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    public function webCatalogsAssignmentDataProvider(): array
    {
        return [
            'no web catalog is used' => [
                'webCatalogsAssignment' => [],
                'expectedWebsiteIds' => []
            ],
            'nor first nor second web catalog is used' => [
                'webCatalogsAssignment' => [
                    self::FIRST_WEBSITE_ID => self::THIRD_WEB_CATALOG_ID,
                    self::SECOND_WEBSITE_ID => self::THIRD_WEB_CATALOG_ID
                ],
                'expectedWebsiteIds' => []
            ],
            'both websites use first web catalog' => [
                'webCatalogsAssignment' => [
                    self::FIRST_WEBSITE_ID => self::FIRST_WEB_CATALOG_ID,
                    self::SECOND_WEBSITE_ID => self::FIRST_WEB_CATALOG_ID
                ],
                'expectedWebsiteIds' => [self::FIRST_WEBSITE_ID, self::SECOND_WEBSITE_ID]
            ],
            'first website uses first web catalog and second website uses second web catalog' => [
                'webCatalogsAssignment' => [
                    self::FIRST_WEBSITE_ID => self::FIRST_WEB_CATALOG_ID,
                    self::SECOND_WEBSITE_ID => self::SECOND_WEB_CATALOG_ID
                ],
                'expectedWebsiteIds' => [self::FIRST_WEBSITE_ID, self::SECOND_WEBSITE_ID]
            ],
        ];
    }

    private function createSegment(): Segment
    {
        return $this->getEntity(Segment::class, ['id' => self::SEGMENT_ID]);
    }

    private function getContentVariant(int $webCatalogId): ContentVariantInterface
    {
        $contentVariant = new ContentVariantStub();

        $webCatalog = $this->createMock(WebCatalogInterface::class);
        $webCatalog->expects($this->any())
            ->method('getId')
            ->willReturn($webCatalogId);

        $contentNode = new ContentNodeStub(123);
        $contentNode->setWebCatalog($webCatalog);

        $contentVariant->setNode($contentNode);

        return $contentVariant;
    }

    public function testIsWebCatalogEnabledWhenFalse()
    {
        $this->helper = new ProductCollectionSegmentHelper($this->contentVariantSegmentProvider);

        $this->assertFalse($this->helper->isEnabled());
    }

    public function testIsWebCatalogEnabledWhenTrue()
    {
        $this->assertTrue($this->helper->isEnabled());
    }
}
