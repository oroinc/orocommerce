<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\Address;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TaxableTest extends \PHPUnit\Framework\TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['identifier', 1],
            ['origin', new Address()],
            ['destination', new Address()],
            ['taxationAddress', new Address()],
            ['quantity', BigDecimal::of('20'), false],
            ['price', BigDecimal::of('10'), false],
            ['amount', BigDecimal::of('100'), false],
            ['items', new \SplObjectStorage(), false],
            ['result', new Result(), false],
            ['context', new \ArrayObject(), false],
            ['className', '\stdClass'],
            ['shippingCost', BigDecimal::of('10'), false],
        ];

        $this->assertPropertyAccessors($this->createTaxable(), $properties);
    }

    public function testAddItem()
    {
        $taxable = $this->createTaxable();
        $item = new Taxable();
        $taxable->addItem($item);
        $this->assertSame($item, $taxable->getItems()->current());
    }

    public function testAddRemoveItem()
    {
        $taxable = $this->createTaxable();
        $item = new Taxable();
        $item2 = new Taxable();
        $taxable->addItem($item);
        $taxable->addItem($item2);
        $items = $taxable->getItems();
        $this->assertSame($item, $items->current());
        $items->next();
        $this->assertSame($item2, $items->current());
        $this->assertNotSame($item2, $taxable->getItems()->current());
        $taxable->removeItem($item);
        $this->assertSame($item2, $items->current());
        $this->assertSame($item2, $taxable->getItems()->current());
    }

    public function testAddContext()
    {
        $taxable = $this->createTaxable();
        $key = 'context_key';
        $value = 'context_value';
        $taxable->addContext($key, $value);
        $this->assertTrue($taxable->getContext()->offsetExists($key));
        $this->assertEquals($value, $taxable->getContext()->offsetGet($key));
    }

    public function getContextValue()
    {
        $key = 'context_key';
        $value = 'context_value';

        $taxable = $this->createTaxable();
        $taxable->addContext($key, $value);
        $this->assertNull($taxable->getContextValue('not_existed_key'));
        $this->assertEquals($value, $taxable->getContextValue($key));
    }

    /**
     * @return Taxable
     */
    protected function createTaxable()
    {
        return new Taxable();
    }

    public function testMakeDestinationAddressTaxable()
    {
        $taxable = $this->createTaxable();
        $destination = new Address();
        $taxable->setDestination($destination);

        $this->assertNull($taxable->getOrigin());
        $this->assertSame($destination, $taxable->getDestination());
        $this->assertNull($taxable->getTaxationAddress());

        $taxable->makeDestinationAddressTaxable();

        $this->assertNull($taxable->getOrigin());
        $this->assertSame($destination, $taxable->getDestination());
        $this->assertSame($destination, $taxable->getTaxationAddress());
    }

    public function testMakeOriginAddressTaxable()
    {
        $taxable = $this->createTaxable();
        $origin = new Address();
        $taxable->setOrigin($origin);

        $this->assertSame($origin, $taxable->getOrigin());
        $this->assertNull($taxable->getDestination());
        $this->assertNull($taxable->getTaxationAddress());

        $taxable->makeOriginAddressTaxable();

        $this->assertSame($origin, $taxable->getOrigin());
        $this->assertNull($taxable->getDestination());
        $this->assertSame($origin, $taxable->getOrigin());
    }

    public function testClone()
    {
        $taxable = $this->createTaxable();
        $item = new Taxable();
        $item2 = new Taxable();
        $taxable->addItem($item);
        $taxable->addItem($item2);
        $taxable->setPrice(BigDecimal::of('10'));
        $taxable->setQuantity(BigDecimal::of('10'));
        $taxable->setOrigin(new Address());
        $taxable->setDestination(new Address());
        $taxable->setTaxationAddress(new Address());
        $taxable->setResult(new Result());

        $clonedTaxable = clone $taxable;

        $this->assertEquals($taxable->getPrice(), $clonedTaxable->getPrice());
        $this->assertNotSame($taxable->getPrice(), $clonedTaxable->getPrice());

        $this->assertEquals($taxable->getQuantity(), $clonedTaxable->getQuantity());
        $this->assertNotSame($taxable->getQuantity(), $clonedTaxable->getQuantity());

        $this->assertEquals($taxable->getOrigin(), $clonedTaxable->getOrigin());
        $this->assertNotSame($taxable->getOrigin(), $clonedTaxable->getOrigin());

        $this->assertEquals($taxable->getDestination(), $clonedTaxable->getDestination());
        $this->assertNotSame($taxable->getDestination(), $clonedTaxable->getDestination());

        $this->assertEquals($taxable->getTaxationAddress(), $clonedTaxable->getTaxationAddress());
        $this->assertNotSame($taxable->getTaxationAddress(), $clonedTaxable->getTaxationAddress());

        $this->assertEquals($taxable->getItems()->current(), $clonedTaxable->getItems()->current());
        $this->assertNotSame($taxable->getItems()->current(), $clonedTaxable->getItems()->current());

        $taxable->getItems()->next();
        $clonedTaxable->getItems()->next();
        $this->assertEquals($taxable->getItems()->current(), $clonedTaxable->getItems()->current());
        $this->assertNotSame($taxable->getItems()->current(), $clonedTaxable->getItems()->current());

        $this->assertEquals($taxable->getResult(), $clonedTaxable->getResult());
        $this->assertNotSame($taxable->getResult(), $clonedTaxable->getResult());
    }
}
