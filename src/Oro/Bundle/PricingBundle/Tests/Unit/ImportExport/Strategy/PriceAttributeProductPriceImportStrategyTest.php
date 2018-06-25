<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\ChainEntityClassNameProvider;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportStrategy;
use Oro\Bundle\SecurityBundle\Owner\OwnerChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatorInterface;

class PriceAttributeProductPriceImportStrategyTest extends TestCase
{
    const PRICE = 10.26;
    const CURRENCY = 'USD';

    /**
     * @var FieldHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $fieldHelper;

    /**
     * @var PriceAttributeProductPriceImportStrategy
     */
    private $strategy;

    protected function setUp()
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->fieldHelper
            ->expects(static::any())
            ->method('getIdentityValues')
            ->willReturn(['value']);
        $this->fieldHelper
            ->expects(static::any())
            ->method('getFields')
            ->willReturn([]);

        $strategyHelper = $this->createMock(ImportStrategyHelper::class);
        $strategyHelper
            ->expects(static::any())
            ->method('isGranted')
            ->willReturn(true);

        $this->strategy = new PriceAttributeProductPriceImportStrategy(
            $this->createMock(EventDispatcherInterface::class),
            $strategyHelper,
            $this->fieldHelper,
            $this->createMock(DatabaseHelper::class),
            $this->createMock(ChainEntityClassNameProvider::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(NewEntitiesHelper::class),
            $this->createMock(DoctrineHelper::class),
            $this->createMock(OwnerChecker::class)
        );
        $this->strategy->setImportExportContext($this->createMock(ContextInterface::class));
        $this->strategy->setEntityName(PriceAttributeProductPrice::class);
    }

    public function testStrategySetsPriceAndQuantity()
    {
        $entity = $this->createAttributePrice(self::PRICE, self::CURRENCY);

        $this->fieldHelper->expects(static::exactly(4))
            ->method('getObjectValue')
            ->withConsecutive(
                [$entity, 'value'],
                [$entity, 'currency'],
                [$entity, 'value'],
                [$entity, 'currency']
            )
            ->willReturnOnConsecutiveCalls(
                self::PRICE,
                self::CURRENCY,
                self::PRICE,
                self::CURRENCY
            );

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertSame(self::PRICE, $entity->getPrice()->getValue());
        static::assertSame(self::CURRENCY, $entity->getPrice()->getCurrency());

        static::assertSame(1, $entity->getQuantity());
    }

    public function testStrategySetsPriceToNull()
    {
        $entity = $this->createAttributePrice(self::PRICE, self::CURRENCY);

        $this->fieldHelper->expects(static::exactly(4))
            ->method('getObjectValue')
            ->withConsecutive(
                [$entity, 'value'],
                [$entity, 'currency'],
                [$entity, 'value'],
                [$entity, 'currency']
            )
            ->willReturnOnConsecutiveCalls(
                self::PRICE,
                null,
                self::PRICE,
                null
            );

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertNull($entity->getPrice());
    }

    /**
     * @param float  $value
     * @param string $currency
     *
     * @return PriceAttributeProductPrice
     */
    private function createAttributePrice(float $value, string $currency): PriceAttributeProductPrice
    {
        $entity = new PriceAttributeProductPrice();

        $reflectionClass = new \ReflectionClass(PriceAttributeProductPrice::class);

        $valueProperty = $reflectionClass->getProperty('value');
        $valueProperty->setAccessible(true);
        $valueProperty->setValue($entity, $value);

        $currencyProperty = $reflectionClass->getProperty('currency');
        $currencyProperty->setAccessible(true);
        $currencyProperty->setValue($entity, $currency);

        return $entity;
    }
}
