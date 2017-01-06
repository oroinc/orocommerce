<?php

namespace Oro\Bundle\FlatRateBundle\Tests\Unit\Method;

use Oro\Bundle\FlatRateBundle\Method\FlatRateMethod;
use Oro\Bundle\FlatRateBundle\Method\FlatRateMethodType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FlatRateMethodTest extends \PHPUnit_Framework_TestCase
{
    /** @internal */
    const LABEL = 'test';

    /** @internal */
    const CHANNEL_ID = 1;

    /** @var FlatRateMethod */
    protected $flatRate;

    protected function setUp()
    {
        $this->flatRate = new FlatRateMethod(self::LABEL, self::CHANNEL_ID);
    }

    public function testGetIdentifier()
    {
        static::assertEquals(
            FlatRateMethod::IDENTIFIER . self::CHANNEL_ID,
            $this->flatRate->getIdentifier()
        );
    }

    public function testIsGrouped()
    {
        static::assertFalse($this->flatRate->isGrouped());
    }

    public function testGetLabel()
    {
        static::assertSame(self::LABEL, $this->flatRate->getLabel());
    }

    public function testGetTypes()
    {
        $types = $this->flatRate->getTypes();
        static::assertCount(1, $types);
        static::assertInstanceOf(FlatRateMethodType::class, $types[0]);
    }

    public function testGetTypeNull()
    {
        static::assertNull($this->flatRate->getType(null));
    }

    public function testGetType()
    {
        $type = $this->flatRate->getType(FlatRateMethodType::IDENTIFIER);
        static::assertInstanceOf(FlatRateMethodType::class, $type);
    }

    public function testGetOptionsConfigurationFormType()
    {
        static::assertEquals(HiddenType::class, $this->flatRate->getOptionsConfigurationFormType());
    }

    public function testGetSortOrder()
    {
        static::assertEquals(10, $this->flatRate->getSortOrder());
    }
}
