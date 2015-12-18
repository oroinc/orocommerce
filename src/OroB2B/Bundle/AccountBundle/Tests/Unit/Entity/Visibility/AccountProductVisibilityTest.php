<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;

class AccountProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    use EntityTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $entity = new AccountProductVisibility();

        $product = new Product();
        $this->assertPropertyAccessors(
            $entity,
            [
                ['id', 1],
                ['product', $product],
                ['account', new Account()],
                ['website', new Website()],
                ['visibility', AccountProductVisibility::CATEGORY],
            ]
        );

        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);

        $this->assertEquals(AccountProductVisibility::ACCOUNT_GROUP, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }

    public function testClone()
    {
        /** @var AccountProductVisibility $entity */
        $entity = $this->getEntity(
            'OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility',
            ['id' => 1]
        );
        $clonedEntity = clone $entity;
        $this->assertNull($clonedEntity->getId());
    }
}
