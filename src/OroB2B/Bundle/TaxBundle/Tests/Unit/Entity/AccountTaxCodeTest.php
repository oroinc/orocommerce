<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;

class AccountTaxCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['code', 'fr4a'],
            ['description', 'description'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createAccountTaxCode(), $properties);
    }

    /**
     * Test AccountTaxCode relations
     */
    public function testRelations()
    {
        $this->assertPropertyCollections($this->createAccountTaxCode(), [
            ['accounts', new Account()],
        ]);
    }

    public function testToString()
    {
        $entity = new AccountTaxCode();
        $this->assertEmpty((string)$entity);
        $entity->setCode('test');
        $this->assertEquals('test', (string)$entity);
    }

    public function testPreUpdate()
    {
        $productTaxCode = $this->createAccountTaxCode();
        $productTaxCode->preUpdate();
        $this->assertInstanceOf('\DateTime', $productTaxCode->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $productTaxCode = $this->createAccountTaxCode();
        $productTaxCode->prePersist();
        $this->assertInstanceOf('\DateTime', $productTaxCode->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $productTaxCode->getCreatedAt());
    }

    /**
     * @return AccountTaxCode
     */
    private function createAccountTaxCode()
    {
        return new AccountTaxCode();
    }
}
