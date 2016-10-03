<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\ProductBundle\Entity\Product;

class AccountGroupProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountGroupProductVisibility();
        $product = new Product();
        $this->assertPropertyAccessors(
            new AccountGroupProductVisibility(),
            [
                ['id', 1],
                ['product', $product],
                ['visibility', AccountGroupProductVisibility::CATEGORY],
            ]
        );
        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);
        $this->assertEquals(AccountGroupProductVisibility::CURRENT_PRODUCT, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }

    public function testClone()
    {
        /** @var AccountGroupProductVisibility $entity */
        $entity = $this->getEntity(
            'Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility',
            ['id' => 1]
        );
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
