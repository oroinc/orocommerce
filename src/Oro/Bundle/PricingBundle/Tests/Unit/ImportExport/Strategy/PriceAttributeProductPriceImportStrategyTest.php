<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\ImportExport\Strategy;

use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityClassNameProviderInterface;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Field\DatabaseHelper;
use Oro\Bundle\ImportExportBundle\Field\RelatedEntityStateHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\ImportStrategyHelper;
use Oro\Bundle\ImportExportBundle\Strategy\Import\NewEntitiesHelper;
use Oro\Bundle\OrganizationBundle\Ownership\EntityOwnershipAssociationsSetter;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\ImportExport\Strategy\PriceAttributeProductPriceImportStrategy;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    protected function setUp(): void
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
            ->method('checkPermissionGrantedForEntity')
            ->willReturn(true);

        $this->strategy = new PriceAttributeProductPriceImportStrategy(
            $this->createMock(EventDispatcherInterface::class),
            $strategyHelper,
            $this->fieldHelper,
            $this->createMock(DatabaseHelper::class),
            $this->createMock(EntityClassNameProviderInterface::class),
            $this->createMock(TranslatorInterface::class),
            $this->createMock(NewEntitiesHelper::class),
            $this->createMock(DoctrineHelper::class),
            $this->createMock(RelatedEntityStateHelper::class)
        );
        $this->strategy->setImportExportContext($this->createMock(ContextInterface::class));
        $this->strategy->setEntityName(PriceAttributeProductPrice::class);
        $this->strategy->setOwnershipSetter($this->createMock(EntityOwnershipAssociationsSetter::class));
    }

    public function testStrategySetsPriceAndQuantity()
    {
        $entity = $this->createAttributePrice(self::PRICE, self::CURRENCY);

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertNotNull($entity->getPrice());
        static::assertSame(self::PRICE, $entity->getPrice()->getValue());
        static::assertSame(self::CURRENCY, $entity->getPrice()->getCurrency());

        static::assertSame(1, $entity->getQuantity());
    }

    public function testStrategySetsPriceToNullIfValueIsNull()
    {
        $entity = $this->createAttributePrice(null, self::CURRENCY);

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertNull($entity->getPrice());
    }

    public function testStrategySetsPriceToNullIfCurrencyIsNull()
    {
        $entity = $this->createAttributePrice(self::PRICE, null);

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertNull($entity->getPrice());
    }

    public function testStrategyReSetsPriceToNull()
    {
        $entity = $this->createAttributePrice(self::PRICE, self::CURRENCY);

        /** @var PriceAttributeProductPrice $entity */
        $entity = $this->strategy->process($entity);

        static::assertNotNull($entity->getPrice());
        static::assertSame(self::PRICE, $entity->getPrice()->getValue());
        static::assertSame(self::CURRENCY, $entity->getPrice()->getCurrency());

        // Entity value become invalid (null)
        $this->setPriceValueAndCurrency($entity, null, self::CURRENCY);

        /** @var PriceAttributeProductPrice $entity */
        $afterProcessEntity = $this->strategy->process($entity);

        static::assertNull($afterProcessEntity->getPrice());
    }

    /**
     * @param PriceAttributeProductPrice $entity
     * @param float  $value
     * @param string $currency
     * @return PriceAttributeProductPrice
     */
    private function setPriceValueAndCurrency(
        PriceAttributeProductPrice $entity,
        $value,
        $currency
    ): PriceAttributeProductPrice {
        $reflectionClass = new \ReflectionObject($entity);

        $valueProperty = $reflectionClass->getProperty('value');
        $valueProperty->setAccessible(true);
        $valueProperty->setValue($entity, $value);

        $currencyProperty = $reflectionClass->getProperty('currency');
        $currencyProperty->setAccessible(true);
        $currencyProperty->setValue($entity, $currency);

        return $entity;
    }

    /**
     * @param float  $value
     * @param string $currency
     *
     * @return PriceAttributeProductPrice
     */
    private function createAttributePrice($value, $currency): PriceAttributeProductPrice
    {
        $entity = new PriceAttributeProductPrice();

        return $this->setPriceValueAndCurrency($entity, $value, $currency);
    }
}
