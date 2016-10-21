<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class AccountTaxCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
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
            ['accountGroups', new AccountGroup()],
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
        $accountTaxCode = $this->createAccountTaxCode();
        $accountTaxCode->preUpdate();
        $this->assertInstanceOf('\DateTime', $accountTaxCode->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $accountTaxCode = $this->createAccountTaxCode();
        $accountTaxCode->prePersist();
        $this->assertInstanceOf('\DateTime', $accountTaxCode->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $accountTaxCode->getCreatedAt());
    }

    /**
     * @return AccountTaxCode
     */
    private function createAccountTaxCode()
    {
        return new AccountTaxCode();
    }

    public function testGetType()
    {
        $this->assertEquals(TaxCodeInterface::TYPE_ACCOUNT, $this->createAccountTaxCode()->getType());
    }
}
