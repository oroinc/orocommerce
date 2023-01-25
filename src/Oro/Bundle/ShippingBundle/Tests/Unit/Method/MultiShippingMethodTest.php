<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Method;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethod;
use Oro\Bundle\ShippingBundle\Method\MultiShippingMethodType;
use Oro\Bundle\ShippingBundle\Provider\MultiShippingCostProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class MultiShippingMethodTest extends TestCase
{
    private const IDENTIFIER = 'multishipping';
    private const LABEL = 'Multi Shipping';
    private const ICON = 'bundles/icon-uri.png';

    private MultiShippingMethod $shippingMethod;

    protected function setUp(): void
    {
        $roundingService = $this->createMock(RoundingServiceInterface::class);
        $multiShippingCostProvider = $this->createMock(MultiShippingCostProvider::class);
        $this->shippingMethod = new MultiShippingMethod(
            self::IDENTIFIER,
            self::LABEL,
            self::ICON,
            true,
            $roundingService,
            $multiShippingCostProvider
        );
    }

    public function testIsGrouped()
    {
        $this->assertFalse($this->shippingMethod->isGrouped());
    }

    public function testIsEnabled()
    {
        $this->assertTrue($this->shippingMethod->isEnabled());
    }

    public function testGetIdentifier()
    {
        $this->assertEquals(self::IDENTIFIER, $this->shippingMethod->getIdentifier());
    }

    public function testGetLabel()
    {
        $this->assertEquals(self::LABEL, $this->shippingMethod->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->shippingMethod->getTypes();
        $this->assertIsArray($types);
        $this->assertCount(1, $types);
        $this->assertInstanceOf(MultiShippingMethodType::class, $types[0]);
    }

    public function testGetType()
    {
        $this->assertInstanceOf(MultiShippingMethodType::class, $this->shippingMethod->getType('primary'));
    }

    public function testGetOptionsConfigurationFormType()
    {
        $this->assertEquals(HiddenType::class, $this->shippingMethod->getOptionsConfigurationFormType());
    }

    public function testGetSortOrder()
    {
        $this->assertEquals(10, $this->shippingMethod->getSortOrder());
    }

    public function testGetIcon()
    {
        $this->assertEquals(self::ICON, $this->shippingMethod->getIcon());
    }
}
