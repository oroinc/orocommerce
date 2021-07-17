<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\CacheBundle\Generator\ObjectCacheKeyGenerator;
use Oro\Bundle\TaxBundle\Entity\TaxValue;
use Oro\Bundle\TaxBundle\Event\TaxEventDispatcher;
use Oro\Bundle\TaxBundle\Factory\TaxFactory;
use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Manager\TaxValueManager;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;
use Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class TaxManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var TaxManager */
    protected $manager;

    /**  @var \PHPUnit\Framework\MockObject\MockObject|TaxFactory */
    protected $factory;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxEventDispatcher */
    protected $eventDispatcher;

    /** @var \PHPUnit\Framework\MockObject\MockObject|TaxValueManager */
    protected $taxValueManager;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|TaxationSettingsProvider */
    protected $settingsProvider;

    /** @var  \PHPUnit\Framework\MockObject\MockObject|ObjectCacheKeyGenerator */
    protected $objectCacheKeyGenerator;

    /** @var bool */
    protected $taxationEnabled = true;

    /** @var CacheProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheProvider;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(TaxFactory::class);

        $this->eventDispatcher = $this->createMock(TaxEventDispatcher::class);

        $this->taxValueManager = $this->createMock(TaxValueManager::class);

        $this->settingsProvider = $this->createMock(TaxationSettingsProvider::class);

        $this->settingsProvider
            ->expects($this->any())
            ->method('isEnabled')
            ->willReturnCallback(function () {
                return $this->taxationEnabled;
            });

        $this->cacheProvider = $this->createMock(CacheProvider::class);
        $this->objectCacheKeyGenerator = $this->createMock(ObjectCacheKeyGenerator::class);

        $this->manager = new TaxManager(
            $this->factory,
            $this->eventDispatcher,
            $this->taxValueManager,
            $this->settingsProvider,
            $this->cacheProvider,
            $this->objectCacheKeyGenerator
        );
    }

    public function testTransformerNotFound()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('TaxTransformerInterface is missing for stdClass');

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method($this->anything());

        $this->manager->loadTax(new \stdClass());
    }

    public function testNewEntity()
    {
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn(new TaxValue());

        $this->manager->loadTax(new \stdClass());
    }

    public function testTaxValue()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::UNIT => new ResultElement([ResultElement::INCLUDING_TAX => 10])]);
        $taxValue->setResult($taxResult);

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $result = $this->manager->loadTax(new \stdClass());
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $result);
        $this->assertSame($taxResult, $result);
    }

    /**
     * @param $objectToTax
     * @return Taxable
     */
    private function configureCacheProviderExpectations($objectToTax)
    {
        $taxable = new Taxable();

        $this->factory->expects($this->exactly(1))->method('create')->willReturn($taxable);
        $cacheKey = 'someCacheKey';
        $this->objectCacheKeyGenerator->expects($this->any())
            ->method('generate')
            ->with($objectToTax, 'tax')
            ->willReturn($cacheKey);
        $this->cacheProvider->expects($this->exactly(2))
            ->method('contains')
            ->with($cacheKey)
            ->willReturnOnConsecutiveCalls(false, true);

        $this->cacheProvider->expects($this->once())
            ->method('save')
            ->with($cacheKey, $taxable);

        $this->cacheProvider->expects($this->once())
            ->method('fetch')
            ->with($cacheKey)
            ->willReturn($taxable);

        return $taxable;
    }

    public function testGetTaxNewResult()
    {
        $objectToTax = new \stdClass();
        $taxable = $this->configureCacheProviderExpectations($objectToTax);

        $this->taxValueManager->expects($this->never())->method($this->anything());

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->callback(
                    function ($dispatchedTaxable) use ($taxable) {
                        /** @var Taxable $dispatchedTaxable */
                        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Taxable', $dispatchedTaxable);
                        $this->assertEquals($taxable, $dispatchedTaxable);

                        /** @var Result $dispatchedResult */
                        $dispatchedResult = $dispatchedTaxable->getResult();
                        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $dispatchedResult);
                        $unit = $dispatchedResult->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $dispatchedResult->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax($objectToTax);
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
    }

    public function testGetTaxLoadResult()
    {
        $taxValue = new TaxValue();
        $taxResult = new Result([Result::ROW => new ResultElement([ResultElement::EXCLUDING_TAX => 10])]);
        $taxValue->setResult($taxResult);

        $objectToTax = new \stdClass();
        $taxable = $this->configureCacheProviderExpectations($objectToTax);
        $taxable->setResult($taxResult);
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $this->eventDispatcher->expects($this->once())->method('dispatch')
            ->with(
                $this->callback(
                    function ($dispatchedTaxable) use ($taxable, $taxResult) {
                        /** @var Taxable $dispatchedTaxable */
                        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Taxable', $dispatchedTaxable);
                        $this->assertEquals($taxable, $dispatchedTaxable);

                        /** @var Result $dispatchedResult */
                        $dispatchedResult = $dispatchedTaxable->getResult();
                        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $dispatchedResult);
                        $this->assertSame($taxResult, $taxResult);
                        /** @var Result $dispatchedResult */
                        $unit = $dispatchedResult->getUnit();
                        $unit->offsetSet(ResultElement::EXCLUDING_TAX, 20);
                        $dispatchedResult->offsetSet(Result::UNIT, $unit);

                        return true;
                    }
                )
            );

        $result = $this->manager->getTax($objectToTax);
        $this->assertInstanceOf('Oro\Bundle\TaxBundle\Model\Result', $result);
        $this->assertEquals(20, $result->getUnit()->getExcludingTax());
        $this->assertEquals(null, $result->getUnit()->getIncludingTax());
        $this->assertEquals(10, $result->getRow()->getExcludingTax());
        $this->assertEquals(null, $result->getRow()->getIncludingTax());
    }

    public function testSaveWithoutItems()
    {
        $entity = new \stdClass();
        $taxValue = new TaxValue();

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $this->factory->expects($this->exactly(3))->method('create')->willReturn($taxable);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())->method('reverseTransform')->willReturnCallback(
            function (Result $result) use ($taxValue) {
                $taxValue->setResult($result);

                return $taxValue;
            }
        );
        $transformer->expects($this->once())->method('transform')->willReturnCallback(
            function (TaxValue $taxValue) {
                return $taxValue->getResult();
            }
        );
        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())->willReturn($taxValue);

        $this->taxValueManager->expects($this->once())->method('saveTaxValue')->with($taxValue);

        $this->assertEquals($taxValue->getResult(), $this->manager->saveTax($entity, false));
    }

    public function testSaveNewEntity()
    {
        $entity = new \stdClass();
        $taxValue = new TaxValue();
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method('getTaxValue');
        $this->taxValueManager->expects($this->never())->method('saveTaxValue')->with($taxValue);

        $this->assertFalse($this->manager->saveTax($entity, false));
    }

    public function testSaveTaxWithItems()
    {
        $entity = new \stdClass();

        $taxableItem = new Taxable();
        $taxableItem->setClassName('stdClass');
        $taxableItem->setIdentifier(1);

        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);
        $taxable->addItem($taxableItem);

        $itemResult = new Result();

        $result = new Result();
        $result->offsetSet(Result::ITEMS, [$itemResult]);

        $taxValue = new TaxValue();
        $taxValue->setResult($result);

        $this->factory->expects($this->exactly(3))->method('create')->willReturn($taxable);

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->exactly(2))
            ->method('reverseTransform')
            ->willReturnCallback(
                function (Result $result) use ($taxValue) {
                    $taxValue->setResult($result);

                    return $taxValue;
                }
            );

        $transformer->expects($this->once())
            ->method('transform')
            ->willReturnCallback(
                function (TaxValue $taxValue) {
                    return $taxValue->getResult();
                }
            );

        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())
            ->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())
            ->willReturn($taxValue);

        $this->taxValueManager->expects($this->exactly(2))
            ->method('saveTaxValue')
            ->with($taxValue);

        $this->manager->saveTax($entity, true);
    }

    public function testSaveTaxWithItemsNewEntity()
    {
        $entity = new \stdClass();
        $taxValue = new TaxValue();
        $taxable = new Taxable();
        $taxable->setClassName('stdClass');
        $this->factory->expects($this->once())->method('create')->willReturn($taxable);

        $this->taxValueManager->expects($this->never())->method('getTaxValue');
        $this->taxValueManager->expects($this->never())->method('saveTaxValue')->with($taxValue);

        $this->assertFalse($this->manager->saveTax($entity));
    }

    public function testRemoveTaxWithoutItems()
    {
        $entity = new \stdClass();
        $taxable = new Taxable();
        $taxable
            ->setClassName('stdClass')
            ->setIdentifier(1);

        $taxValue = new TaxValue();

        $this->factory->expects($this->once())
            ->method('create')
            ->with($entity)
            ->willReturn($taxable);

        $this->taxValueManager
            ->expects($this->once())
            ->method('findTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())
            ->willReturn($taxValue);

        $this->taxValueManager
            ->expects($this->once())
            ->method('removeTaxValue')
            ->with($taxValue)
            ->willReturn(true);

        $this->assertTrue($this->manager->removeTax($entity, false));
    }

    public function testRemoveTaxWithoutItemsWhenTaxValueNotFound()
    {
        $entity = new \stdClass();
        $taxable = new Taxable();
        $taxable
            ->setClassName('stdClass')
            ->setIdentifier(1);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($entity)
            ->willReturn($taxable);

        $this->taxValueManager
            ->expects($this->once())
            ->method('findTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())
            ->willReturn(null);

        $this->taxValueManager
            ->expects($this->never())
            ->method('removeTaxValue');

        $this->assertFalse($this->manager->removeTax($entity, false));
    }

    public function testRemoveTaxWithItems()
    {
        $entity = new \stdClass();
        $taxable = new Taxable();
        $taxable
            ->setClassName('stdClass')
            ->setIdentifier(1);

        $taxValue = new TaxValue();

        $itemTaxable = new Taxable();
        $itemTaxable
            ->setClassName('stdClass')
            ->setIdentifier(2);

        $itemTaxValue = new TaxValue();

        $taxable->addItem($itemTaxable);

        $this->factory->expects($this->once())
            ->method('create')
            ->with($entity)
            ->willReturn($taxable);

        $this->taxValueManager
            ->expects($this->exactly(2))
            ->method('findTaxValue')
            ->withConsecutive(
                [$itemTaxable->getClassName(), $itemTaxable->getIdentifier()],
                [$taxable->getClassName(), $taxable->getIdentifier()]
            )
            ->willReturnOnConsecutiveCalls($taxValue, $itemTaxValue);

        $this->taxValueManager
            ->expects($this->exactly(2))
            ->method('removeTaxValue')
            ->withConsecutive([$itemTaxValue], [$taxValue])
            ->willReturn(true);

        $this->assertTrue($this->manager->removeTax($entity, true));
    }

    public function testCrateTaxValue()
    {
        $objectToTax = new \stdClass();
        $taxable = $this->configureCacheProviderExpectations($objectToTax);
        $taxable->setClassName('stdClass');
        $taxable->setIdentifier(1);

        $taxValue = new TaxValue();
        $taxValue->setResult(new Result());

        /** @var \PHPUnit\Framework\MockObject\MockObject|TaxTransformerInterface $transformer */
        $transformer = $this->createMock('Oro\Bundle\TaxBundle\Transformer\TaxTransformerInterface');
        $transformer->expects($this->once())
            ->method('reverseTransform')
            ->willReturnCallback(
                function (Result $result) use ($taxValue) {
                    $taxValue->setResult($result);

                    return $taxValue;
                }
            );

        $transformer->expects($this->once())
            ->method('transform')
            ->willReturnCallback(
                function (TaxValue $taxValue) {
                    return $taxValue->getResult();
                }
            );

        $this->manager->addTransformer('stdClass', $transformer);

        $this->taxValueManager->expects($this->once())
            ->method('getTaxValue')
            ->with($taxable->getClassName(), $taxable->getIdentifier())
            ->willReturn($taxValue);

        $this->assertEquals($taxValue, $this->manager->createTaxValue($objectToTax));
    }

    public function testExceptionWhenTaxationDisabled()
    {
        $this->expectException(\Oro\Bundle\TaxBundle\Exception\TaxationDisabledException::class);
        $this->taxationEnabled = false;

        $this->manager->getTax(new \stdClass());
    }
}
