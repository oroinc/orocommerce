<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountProductVisibility;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class AccountProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

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
                ['visibility', AccountProductVisibility::CATEGORY],
            ]
        );

        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);

        $this->assertEquals(AccountProductVisibility::CATEGORY, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }
}
