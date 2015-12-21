<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

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
                ['accountGroup', new AccountGroup()],
                ['website', new Website()],
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
            'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility',
            ['id' => 1]
        );
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
