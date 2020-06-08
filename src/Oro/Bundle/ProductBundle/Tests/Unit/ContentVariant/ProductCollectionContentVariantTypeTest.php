<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant;

use Oro\Bundle\ProductBundle\Api\Model\ProductCollection;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Component\Routing\RouteData;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class ProductCollectionContentVariantTypeTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $authorizationChecker;

    /**
     * @var ProductCollectionContentVariantType
     */
    private $type;

    protected function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->type = new ProductCollectionContentVariantType($this->authorizationChecker);
    }

    public function testGetTitle()
    {
        $this->assertEquals('oro.product.content_variant.product_collection.label', $this->type->getTitle());
    }

    public function testGetFormType()
    {
        $this->assertEquals(ProductCollectionVariantType::class, $this->type->getFormType());
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
        $contentVariant->setOverrideVariantConfiguration(true);

        $this->assertEquals(
            new RouteData(
                ProductCollectionContentVariantType::PRODUCT_COLLECTION_ROUTE_NAME,
                [
                    ProductCollectionContentVariantType::CONTENT_VARIANT_ID_KEY => $contentVariant->getId(),
                    ProductCollectionContentVariantType::OVERRIDE_VARIANT_CONFIGURATION_KEY => true
                ]
            ),
            $this->type->getRouteData($contentVariant)
        );
    }

    public function testGetAttachedEntity()
    {
        /** @var ContentVariantStub **/
        $contentVariant = new ContentVariantStub();
        /** @var Segment $segment */
        $segment = $this->getEntity(Segment::class, ['id' => 42]);
        $contentVariant->setProductCollectionSegment($segment);

        $this->assertEquals(
            $segment,
            $this->type->getAttachedEntity($contentVariant)
        );
    }

    public function testGetApiResourceClassName()
    {
        $this->assertEquals(ProductCollection::class, $this->type->getApiResourceClassName());
    }

    public function testGetApiResourceIdentifierDqlExpression()
    {
        $this->assertEquals(
            'e.id',
            $this->type->getApiResourceIdentifierDqlExpression('e')
        );
    }
}
