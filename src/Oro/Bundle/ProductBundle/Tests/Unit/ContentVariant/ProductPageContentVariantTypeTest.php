<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductPageContentVariantTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationChecker;

    /**
     * @var ProductPageContentVariantType
     */
    private $type;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->type = new ProductPageContentVariantType(
            $this->authorizationChecker,
            $this->getPropertyAccessor()
        );
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.product.content_variant.product_page.label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(ProductPageVariantType::class, $this->type->getFormType());
    }

    public function testIsAllowed()
    {
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with('oro_product_view')
            ->willReturn(true);
        $this->assertTrue($this->type->isAllowed());
    }

    public function testGetRouteData()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $contentVariant->setProductPageProduct($product);

        $this->assertEquals(
            new RouteData('oro_product_frontend_product_view', ['id' => 42]),
            $this->type->getRouteData($contentVariant)
        );
    }

    public function testGetAttachedEntity()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();
        /** @var Product $product */
        $product = $this->getEntity(Product::class, ['id' => 42]);
        $contentVariant->setProductPageProduct($product);

        $this->assertEquals(
            $product,
            $this->type->getAttachedEntity($contentVariant)
        );
    }

    public function testGetApiResourceClassName()
    {
        $this->assertEquals(Product::class, $this->type->getApiResourceClassName());
    }

    public function testGetApiResourceIdentifierDqlExpression()
    {
        $this->assertEquals(
            'IDENTITY(e.product_page_product)',
            $this->type->getApiResourceIdentifierDqlExpression('e')
        );
    }
}
