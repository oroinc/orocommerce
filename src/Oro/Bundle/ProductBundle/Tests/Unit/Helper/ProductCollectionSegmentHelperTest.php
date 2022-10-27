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

    const SEGMENT_ID = 123;
    const FIRST_WEB_CATALOG_ID = 111;
    const SECOND_WEB_CATALOG_ID = 777;
    const THIRD_WEB_CATALOG_ID = 999;
    const FIRST_WEBSITE_ID = 1;
    const SECOND_WEBSITE_ID = 2;

    /**
     * @var ContentVariantSegmentProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $contentVariantSegmentProvider;

    /**
     * @var WebCatalogUsageProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $webCatalogUsageProvider;

    /**
     * @var ProductCollectionSegmentHelper
     */
    private $helper;

    protected function setUp(): void
    {
        $this->contentVariantSegmentProvider = $this->getMockBuilder(ContentVariantSegmentProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->webCatalogUsageProvider = $this->createMock(WebCatalogUsageProviderInterface::class);
        $this->helper = new ProductCollectionSegmentHelper(
            $this->contentVariantSegmentProvider,
            $this->webCatalogUsageProvider
        );
    }

    public function testGetWebsiteIdsBySegmentWhenWebCatalogBundleIsDisabled()
    {
        $this->helper = new ProductCollectionSegmentHelper($this->contentVariantSegmentProvider);

        $this->contentVariantSegmentProvider
            ->expects($this->never())
            ->method('getContentVariants');

        $this->assertEmpty($this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    public function testGetWebsiteIdsBySegmentWhenNoContentVariantsExist()
    {
        $this->contentVariantSegmentProvider
            ->expects($this->once())
            ->method('getContentVariants')
            ->willReturn([]);

        $this->assertEmpty($this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    /**
     * @dataProvider webCatalogsAssignmentDataProvider
     */
    public function testGetWebsiteIdsBySegment(array $webCatalogAssignment, array $expectedWebsiteIds)
    {
        $firstContentVariant = $this->createContentVariant(self::FIRST_WEB_CATALOG_ID);
        $secondContentVariant = $this->createContentVariant(self::SECOND_WEB_CATALOG_ID);

        $this->contentVariantSegmentProvider
            ->expects($this->once())
            ->method('getContentVariants')
            ->willReturn([$firstContentVariant, $secondContentVariant]);

        $this->webCatalogUsageProvider
            ->expects($this->once())
            ->method('getAssignedWebCatalogs')
            ->willReturn($webCatalogAssignment);

        $this->assertEquals($expectedWebsiteIds, $this->helper->getWebsiteIdsBySegment($this->createSegment()));
    }

    /**
     * @return array
     */
    public function webCatalogsAssignmentDataProvider()
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

    /**
     * @return Segment
     */
    private function createSegment()
    {
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => self::SEGMENT_ID]);

        return $segment;
    }

    /**
     * @param int $webCatalogId
     * @return ContentVariantInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createContentVariant($webCatalogId)
    {
        $contentVariant = new ContentVariantStub();

        /** @var WebCatalogInterface|\PHPUnit\Framework\MockObject\MockObject $webCatalog */
        $webCatalog = $this->createMock(WebCatalogInterface::class);

        $webCatalog
            ->expects($this->any())
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
