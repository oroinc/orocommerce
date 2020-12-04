<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\ContentVariantDataProvider;
use Oro\Bundle\WebCatalogBundle\Provider\RequestWebContentVariantProvider;
use Oro\Component\Testing\Unit\EntityTrait;

class ContentVariantDataProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var RequestWebContentVariantProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $requestWebContentVariantProvider;

    /** @var ContentVariantDataProvider */
    private $dataProvider;

    protected function setUp(): void
    {
        $this->requestWebContentVariantProvider = $this->createMock(RequestWebContentVariantProvider::class);

        $this->dataProvider = new ContentVariantDataProvider(
            $this->requestWebContentVariantProvider
        );
    }

    public function testGetFromRequestWhenContentVariantIsNull()
    {
        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);

        $this->assertNull($this->dataProvider->getFromRequest());
    }

    public function testGetContentVariantTypeWhenContentVariantIsNull()
    {
        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn(null);

        $this->assertNull($this->dataProvider->getContentVariantType());
    }

    public function testGetFromRequestWhenContentVariantExists()
    {
        $contentVariant = $this->createMock(ContentVariant::class);

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $this->assertSame($contentVariant, $this->dataProvider->getFromRequest());
    }

    public function testGetContentVariantTypeWhenContentVariantExists()
    {
        $contentVariant = $this->createMock(ContentVariant::class);
        $contentVariant->expects($this->once())
            ->method('getType')
            ->willReturn('content_variant_type');

        $this->requestWebContentVariantProvider->expects($this->once())
            ->method('getContentVariant')
            ->willReturn($contentVariant);

        $this->assertSame('content_variant_type', $this->dataProvider->getContentVariantType());
    }
}
