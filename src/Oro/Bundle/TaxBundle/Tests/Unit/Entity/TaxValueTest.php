<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Model\Result;

class TaxValueTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['result', new Result(['test' => 'value']), false],
            ['entityClass', 'Oro\Bundle\SomeBundle\Entity\EntityClass'],
            ['entityId', 5],
            ['id', 5],
            ['address', 'Kiev, SomeStreet str., 55'],
        ];

        $this->assertPropertyAccessors($this->createTaxValue(), $properties);
    }

    /**
     * @return TaxValue
     */
    private function createTaxValue()
    {
        return new TaxValue();
    }
}
