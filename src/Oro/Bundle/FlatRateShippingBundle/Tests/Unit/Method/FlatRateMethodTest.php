<?php

namespace Oro\Bundle\FlatRateShippingBundle\Tests\Unit\Method;

use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateShippingBundle\Method\FlatRateMethodType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FlatRateMethodTest extends \PHPUnit\Framework\TestCase
{
    private const LABEL = 'test';
    private const IDENTIFIER = 'flat_rate';
    private const ICON = 'bundles/icon-uri.png';

    private FlatRateMethod $flatRate;

    protected function setUp(): void
    {
        $this->flatRate = new FlatRateMethod(self::IDENTIFIER, self::LABEL, self::ICON, true);
    }

    public function testGetIdentifier()
    {
        self::assertEquals(self::IDENTIFIER, $this->flatRate->getIdentifier());
    }

    public function testIsGrouped()
    {
        self::assertFalse($this->flatRate->isGrouped());
    }

    public function testIsEnabled()
    {
        self::assertTrue($this->flatRate->isEnabled());
    }

    public function testGetLabel()
    {
        self::assertSame(self::LABEL, $this->flatRate->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->flatRate->getTypes();
        self::assertCount(1, $types);
        self::assertInstanceOf(FlatRateMethodType::class, $types[0]);
    }

    public function testGetType()
    {
        self::assertInstanceOf(
            FlatRateMethodType::class,
            $this->flatRate->getType(FlatRateMethodType::IDENTIFIER)
        );
    }

    public function testGetOptionsConfigurationFormType()
    {
        self::assertEquals(HiddenType::class, $this->flatRate->getOptionsConfigurationFormType());
    }

    public function testGetSortOrder()
    {
        self::assertEquals(10, $this->flatRate->getSortOrder());
    }

    public function testGetIcon()
    {
        self::assertSame(self::ICON, $this->flatRate->getIcon());
    }
}
