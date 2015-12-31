<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Model\Taxable;

class TaxableTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['identifier', 1],
            ['origin', 'address'],
            ['destination', 'address'],
            ['quantity', 20],
            ['price', 30],
            ['amount', 100]
        ];

        $this->assertPropertyAccessors($this->createTaxable(), $properties);
    }

    public function testRelations()
    {
        $this->assertPropertyCollections($this->createTaxable(), [
            ['items', new ArrayCollection()],
        ]);
    }

    /**
     * @return Taxable
     */
    private function createTaxable()
    {
        return new Taxable();
    }
}
