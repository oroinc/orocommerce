<?php

namespace Oro\Bundle\FixedProductShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethod;
use Oro\Bundle\FixedProductShippingBundle\Method\FixedProductMethodType;
use Oro\Bundle\FixedProductShippingBundle\Provider\ShippingCostProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FixedProductMethodTest extends TestCase
{
    public const LABEL = 'test';
    public const IDENTIFIER = 'fixed_product';
    public const ICON = 'bundles/icon-uri.png';

    protected FixedProductMethod $fixedProduct;

    /**
     * {@inheritDoc}
     */
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

    public function testGetTypeNull(): void
    {
        $this->assertNull($this->fixedProduct->getType(null));
    }

    public function testGetType(): void
    {
        $type = $this->fixedProduct->getType(FixedProductMethodType::IDENTIFIER);
        $this->assertInstanceOf(FixedProductMethodType::class, $type);
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
