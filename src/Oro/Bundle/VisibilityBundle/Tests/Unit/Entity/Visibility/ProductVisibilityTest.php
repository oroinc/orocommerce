<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new ProductVisibility();
        $product = new Product();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['product', $product],
                ['visibility', ProductVisibility::CONFIG],
            ]
        );
        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);
        $this->assertEquals(ProductVisibility::CATEGORY, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }

    public function testClone()
    {
        /** @var ProductVisibility $entity */
        $entity = $this->getEntity('Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility', ['id' => 1]);
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
