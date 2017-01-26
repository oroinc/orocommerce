<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Entity\Scope;

class CustomerProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new CustomerProductVisibility();

        $product = new Product();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['product', $product],
                ['visibility', CustomerProductVisibility::CATEGORY],
                ['scope', new Scope()]
            ]
        );

        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);

        $this->assertEquals(CustomerProductVisibility::ACCOUNT_GROUP, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }

    public function testClone()
    {
        /** @var CustomerProductVisibility $entity */
        $entity = $this->getEntity(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility',
            ['id' => 1]
        );
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
