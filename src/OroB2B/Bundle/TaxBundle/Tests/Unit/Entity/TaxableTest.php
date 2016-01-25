<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

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
            ['price', '10'],
            ['amount', '100'],
            ['items', new \SplObjectStorage(), false],
            ['className', '\stdClass'],
        ];

        $this->assertPropertyAccessors($this->createTaxable(), $properties);
    }

    public function testAddItem()
    {
        $taxable = $this->createTaxable();
        $item = new \stdClass();
        $taxable->addItem($item);
        $taxable->getItems()->rewind();
        $this->assertSame($item, $taxable->getItems()->current());
    }

    public function testAddRemoveItem()
    {
        $taxable = $this->createTaxable();
        $item = new \stdClass();
        $item2 = new \stdClass();
        $taxable->addItem($item);
        $taxable->addItem($item2);
        $taxable->getItems()->rewind();
        $this->assertSame($item, $taxable->getItems()->current());
        $taxable->getItems()->next();
        $this->assertSame($item2, $taxable->getItems()->current());

        $taxable->removeItem($item);
        $taxable->getItems()->rewind();
        $this->assertSame($item2, $taxable->getItems()->current());
    }

    /**
     * @return Taxable
     */
    protected function createTaxable()
    {
        return new Taxable();
    }
}
