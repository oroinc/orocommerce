<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\RelatedProducts;;

use Oro\Bundle\ProductBundle\RelatedItem\ConfigProvider\RelatedProductsConfigProvider;
use Oro\Bundle\ProductBundle\RelatedItem\RelatedProductAssigner;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;

class RelatedProductAssignerTest extends \PHPUnit_Framework_TestCase
{
    /** @var RelatedProductAssigner */
    protected $assigner;

    /** @var RelatedProductsConfigProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configProvider;

    protected function setUp()
    {
        $this->configProvider = $this->getMockBuilder(RelatedProductsConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->assigner = new RelatedProductAssigner($this->configProvider);
    }

    public function testProductCanBeAssignedToTheOther()
    {
        // given
        $productA = new Product();
        $productB = new Product();
        $this->getLimitShouldReturn(1);
        $this->enableRelatedProducts();

        // when
        $this->assigner->assignRelation($productA, $productB);

        // then
        $this->assertSame($productA->getRelatedToProducts()->first(), $productB);
    }

    public function testProductCannotBeAssignedToItself()
    {
        $productA = new Product();
        $this->enableRelatedProducts();

        $this->expectException(\InvalidArgumentException::class);

        $this->assigner->assignRelation($productA, $productA);
    }

    public function testProductWillNotBeAssignedTwiceToTheSameProduct()
    {
        // given
        $productA = new Product();
        $productB = new Product();
        $this->getLimitShouldReturn(2);
        $this->enableRelatedProducts();

        // when
        $this->assigner->assignRelation($productA, $productB);
        $this->assigner->assignRelation($productA, $productB);

        // then
        $this->assertCount(1, $productA->getRelatedToProducts());
    }

    public function testProductCanBeUnassigned()
    {
        // given
        $productA = new Product();
        $productB = new Product();
        $this->getLimitShouldReturn(2);
        $this->enableRelatedProducts();

        // when
        $this->assigner->assignRelation($productA, $productB);
        $this->assigner->removeRelation($productA, $productB);

        // then
        $this->assertCount(0, $productA->getRelatedToProducts());
    }

    public function testNothingHappensWhenTryToRemoveNonExistingRelation()
    {
        // given
        $productA = new Product();
        $productB = new Product();

        // when
        $this->assigner->removeRelation($productA, $productB);

        // then
        $this->assertCount(0, $productA->getRelatedToProducts());
    }

    public function testThrowExceptionWhenTryToExceedRelationLimitForAProduct()
    {
        // given
        $productA = new Product();
        $productB = new Product();
        $productC = new Product();
        $this->getLimitShouldReturn(1);
        $this->enableRelatedProducts();

        $this->assigner->assignRelation($productA, $productB);

        $this->expectException(\Exception::class);

        $this->assigner->assignRelation($productA, $productC);
    }

    public function testProductCanBeAssignedAfterIncreasingLimit()
    {
        // given
        $productA = new Product();
        $productB = new Product();
        $productC = new Product();
        $productD = new Product();

        $this->getLimitShouldReturn(2);
        $this->enableRelatedProducts();

        $this->assigner->assignRelation($productA, $productB);
        $this->assigner->assignRelation($productA, $productC);

        $this->expectException(\OverflowException::class);

        $this->assigner->assignRelation($productA, $productD);
    }

    public function testRelationCannotBeAssignedIfRelatedProductIsDisable()
    {
        $productA = new Product();
        $productB = new Product();
        $this->getLimitShouldReturn(1);
        $this->disableRelatedProducts();

        $this->expectException(\LogicException::class);

        $this->assigner->assignRelation($productA, $productB);
    }

    /**
     * @param int $limit
     */
    private function getLimitShouldReturn($limit)
    {
        $this->configProvider->expects($this->any())
            ->method('getLimit')
            ->willReturn($limit);
    }

    private function enableRelatedProducts()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(true);
    }

    private function disableRelatedProducts()
    {
        $this->configProvider->expects($this->any())
            ->method('isEnabled')
            ->willReturn(false);
    }
}
