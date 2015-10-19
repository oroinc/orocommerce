<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity\Visibility;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupProductVisibility;
use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class AccountGroupProductVisibilityTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

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
                ['visibility', AccountGroupProductVisibility::CATEGORY],
            ]
        );
        $entity->setTargetEntity($product);
        $this->assertEquals($entity->getTargetEntity(), $product);
        $this->assertEquals(AccountGroupProductVisibility::CATEGORY, $entity->getDefault($product));

        $this->assertInternalType('array', $entity->getVisibilityList($product));
        $this->assertNotEmpty($entity->getVisibilityList($product));
    }
}
