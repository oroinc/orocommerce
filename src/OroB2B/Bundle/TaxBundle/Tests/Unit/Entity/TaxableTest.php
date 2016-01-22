<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\CurrencyBundle\Model\Price;
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
            ['price', Price::create(30, 'USD')],
            ['amount', 100]
        ];

        $this->assertPropertyAccessors($this->createTaxable(), $properties);
    }

    public function testRelations()
    {
        $this->assertPropertyCollections($this->createTaxable(), [
            ['items', new \SplObjectStorage()],
        ]);
    }

    public function testItems()
    {
        $taxable = $this->createTaxable();
        $items = new \SplObjectStorage(['some', 'items']);
        $taxable->setItems($items);
        $this->assertSame($items, $taxable->getItems());
    }

    /**
     * @return Taxable
     */
    private function createTaxable()
    {
        return new Taxable();
    }
}
