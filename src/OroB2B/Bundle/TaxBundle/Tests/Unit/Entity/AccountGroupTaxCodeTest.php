<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountGroup;
use OroB2B\Bundle\TaxBundle\Entity\AccountGroupTaxCode;

class AccountGroupTaxCodeTest extends \PHPUnit_Framework_TestCase
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

        $this->assertPropertyAccessors($this->createAccountGroupTaxCode(), $properties);
    }

    /**
     * Test AccountGroupTaxCode relations
     */
    public function testRelations()
    {
        $this->assertPropertyCollections($this->createAccountGroupTaxCode(), [
            ['accountGroups', new AccountGroup()],
        ]);
    }

    public function testToString()
    {
        $entity = new AccountGroupTaxCode();
        $this->assertEmpty((string)$entity);
        $entity->setCode('test');
        $this->assertEquals('test', (string)$entity);
    }

    public function testPreUpdate()
    {
        $accountGroupTaxCode = $this->createAccountGroupTaxCode();
        $accountGroupTaxCode->preUpdate();
        $this->assertInstanceOf('\DateTime', $accountGroupTaxCode->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $accountTaxCode = $this->createAccountGroupTaxCode();
        $accountTaxCode->prePersist();
        $this->assertInstanceOf('\DateTime', $accountTaxCode->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $accountTaxCode->getCreatedAt());
    }

    /**
     * @return AccountGroupTaxCode
     */
    private function createAccountGroupTaxCode()
    {
        return new AccountGroupTaxCode();
    }
}
