<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethod;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FixedProductMethodTest extends \PHPUnit\Framework\TestCase
{
    private const LABEL = 'test';
    private const IDENTIFIER = 'fixed_product';
    private const ICON = 'bundles/icon-uri.png';

    private FixedProductMethod $fixedProduct;

    protected function setUp(): void
    {
        $this->fixedProduct = new FixedProductMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON,
            true,
            $this->createMock(RoundingServiceInterface::class),
            $this->createMock(ShippingCostProvider::class)
        );
    }

    public function testGetIdentifier(): void
    {
        $this->assertEquals(self::IDENTIFIER, $this->fixedProduct->getIdentifier());
    }

    public function testIsGrouped(): void
    {
        $this->assertFalse($this->fixedProduct->isGrouped());
    }

    public function testIsEnabled(): void
    {
        $this->assertTrue($this->fixedProduct->isEnabled());
    }

    public function testGetLabel(): void
    {
        $this->assertSame(self::LABEL, $this->fixedProduct->getLabel());
    }

    public function testGetTypes(): void
    {
        $types = $this->fixedProduct->getTypes();
        $this->assertCount(1, $types);
        $this->assertInstanceOf(FixedProductMethodType::class, $types[0]);
    }

    public function testGetType(): void
    {
        $this->assertInstanceOf(
            FixedProductMethodType::class,
            $this->fixedProduct->getType(FixedProductMethodType::IDENTIFIER)
        );
    }

    public function testGetOptionsConfigurationFormType(): void
    {
        $this->assertEquals(HiddenType::class, $this->fixedProduct->getOptionsConfigurationFormType());
    }

    public function testGetSortOrder(): void
    {
        $this->assertEquals(10, $this->fixedProduct->getSortOrder());
    }

    public function testGetIcon(): void
    {
        $this->assertSame(self::ICON, $this->fixedProduct->getIcon());
    }
}
