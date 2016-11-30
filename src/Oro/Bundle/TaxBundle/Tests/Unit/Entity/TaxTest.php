<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\Tax;

class TaxTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;
    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createTaxEntity(), [
            ['id', 1],
            ['code', 'CodeExample43'],
            ['description', 'tax description'],
            ['rate', 23.4],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
    }

    public function testPrePersist()
    {
        $tax = $this->createTaxEntity();
        $tax->prePersist();
        $this->assertInstanceOf('DateTime', $tax->getCreatedAt());
        $this->assertInstanceOf('DateTime', $tax->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $tax = $this->createTaxEntity();
        $tax->preUpdate();
        $this->assertInstanceOf('DateTime', $tax->getUpdatedAt());
    }

    /**
     * @return Tax
     */
    protected function createTaxEntity()
    {
        return new Tax();
    }

    public function testToString()
    {
        $tax = $this->createTaxEntity();
        $tax->setCode('code');
        $this->assertEquals('code', (string)$tax);
    }
}
