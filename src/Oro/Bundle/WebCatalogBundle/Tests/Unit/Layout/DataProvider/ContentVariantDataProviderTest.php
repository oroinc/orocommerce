<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Layout\DataProvider\ContentVariantDataProvider;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class ContentVariantDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var RequestStack|\PHPUnit_Framework_MockObject_MockObject
     */
    private $requestStack;

    protected function setUp()
    {
        $this->requestStack = $this->createMock(RequestStack::class);
    }

    public function testGetFromRequestWhenNoAttributeSet()
    {
        $request = new Request();

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $dataProvider = new ContentVariantDataProvider($this->requestStack);
        $this->assertNull($dataProvider->getFromRequest());
    }

    public function testGetFromRequestWhenRequestIsNull()
    {
        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $dataProvider = new ContentVariantDataProvider($this->requestStack);
        $this->assertNull($dataProvider->getFromRequest());
    }

    public function testGetFromRequestWhenAttributeSet()
    {
        $contentVariant = new ContentVariant();
        $request = new Request([], [], ['_content_variant' => $contentVariant]);

        $this->requestStack
            ->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $dataProvider = new ContentVariantDataProvider($this->requestStack);
        $this->assertSame($contentVariant, $dataProvider->getFromRequest());
    }
}
