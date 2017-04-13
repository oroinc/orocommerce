<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductCollectionContentVariantTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    /**
     * @var ProductCollectionContentVariantType
     */
    private $type;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ProductCollectionContentVariantType($this->securityFacade);
    }

    public function testGetName()
    {
        $this->assertEquals('product_collection', $this->type->getName());
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.product.content_variant.product_collection.label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(ProductCollectionVariantType::NAME, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->with('oro_product_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    public function testGetRouteData()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();

        $this->assertEquals(
            new RouteData('oro_product_frontend_product_index'),
            $this->type->getRouteData($contentVariant)
        );
    }
}
