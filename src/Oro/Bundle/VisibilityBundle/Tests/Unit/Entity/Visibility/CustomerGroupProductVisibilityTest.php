<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerGroupProductVisibilityTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CustomerGroupProductVisibility();
        $product = new Product();
        $this->assertPropertyAccessors(
            new CustomerGroupProductVisibility(),
            [
                ['id', 1],
                ['product', $product],
                ['visibility', CustomerGroupProductVisibility::CATEGORY],
                ['scope', new Scope()]
            ]
        );
        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);
        $this->assertEquals(CustomerGroupProductVisibility::CURRENT_PRODUCT, $entity->getDefault($product));

        $this->assertIsArray($entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }

    public function testClone()
    {
        /** @var CustomerGroupProductVisibility $entity */
        $entity = $this->getEntity(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility',
            ['id' => 1]
        );
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
