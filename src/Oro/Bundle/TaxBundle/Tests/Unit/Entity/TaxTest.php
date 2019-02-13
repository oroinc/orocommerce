<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TaxTest extends \PHPUnit\Framework\TestCase
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
