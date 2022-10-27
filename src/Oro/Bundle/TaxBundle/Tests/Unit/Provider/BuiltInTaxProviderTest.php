<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Provider\BuiltInTaxProvider;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class BuiltInTaxProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TaxManager|\PHPUnit\Framework\MockObject\MockObject */
    private $taxManager;

    /** @var BuiltInTaxProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->taxManager = $this->createMock(TaxManager::class);

        $this->provider = new BuiltInTaxProvider($this->taxManager);
    }

    public function testGetLabel()
    {
        $this->assertEquals('oro.tax.providers.built_in.label', $this->provider->getLabel());
    }

    public function testIsApplicable()
    {
        $this->assertTrue($this->provider->isApplicable());
    }

    public function testCreateTaxValue()
    {
        $object = new \stdClass();
        $taxValue = new TaxValue();

        $this->taxManager->expects($this->once())
            ->method('createTaxValue')
            ->with($object)
            ->willReturn($taxValue);

        $this->assertEquals($taxValue, $this->provider->createTaxValue($object));
    }

    public function testLoadTax()
    {
        $object = new \stdClass();
        $result = new Result();

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($object)
            ->willReturn($result);

        $this->assertEquals($result, $this->provider->loadTax($object));
    }

    public function testGetTax()
    {
        $object = new \stdClass();
        $result = new Result();

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($object)
            ->willReturn($result);

        $this->assertEquals($result, $this->provider->getTax($object));
    }

    public function testSaveTax()
    {
        $object = new \stdClass();
        $taxValue = $this->getEntity(TaxValue::class, ['id' => 1]);
        $storedTaxResult = new Result();
        $calculatedTaxResult = new Result();
        $calculatedTaxResult->offsetSet(Result::TOTAL, ResultElement::create(1, 1, 1));

        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($object)
            ->willReturn($taxValue);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($object)
            ->willReturn($storedTaxResult);

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($object)
            ->willReturn($calculatedTaxResult);

        $this->taxManager->expects($this->once())
            ->method('saveTax')
            ->with($object, false)
            ->willReturn($storedTaxResult);

        $this->assertEquals($storedTaxResult, $this->provider->saveTax($object));
    }

    public function testSaveTaxEqualsStoredAndCalculatedResults()
    {
        $object = new \stdClass();
        $taxValue = $this->getEntity(TaxValue::class, ['id' => 1]);
        $storedTaxResult = new Result();
        $calculatedTaxResult = new Result();

        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($object)
            ->willReturn($taxValue);

        $this->taxManager->expects($this->once())
            ->method('loadTax')
            ->with($object)
            ->willReturn($storedTaxResult);

        $this->taxManager->expects($this->once())
            ->method('getTax')
            ->with($object)
            ->willReturn($calculatedTaxResult);

        $this->taxManager->expects($this->never())
            ->method('saveTax');

        $this->assertNull($this->provider->saveTax($object));
    }

    public function testSaveTaxNewTaxValue()
    {
        $object = new \stdClass();
        $taxValue = new TaxValue();
        $result = new Result();

        $this->taxManager->expects($this->once())
            ->method('getTaxValue')
            ->with($object)
            ->willReturn($taxValue);

        $this->taxManager->expects($this->once())
            ->method('saveTax')
            ->with($object, false)
            ->willReturn($result);

        $this->assertEquals($result, $this->provider->saveTax($object));
    }

    public function testRemoveTax()
    {
        $object = new \stdClass();

        $this->taxManager->expects($this->once())
            ->method('removeTax')
            ->with($object)
            ->willReturn(true);

        $this->assertTrue($this->provider->removeTax($object));
    }
}
