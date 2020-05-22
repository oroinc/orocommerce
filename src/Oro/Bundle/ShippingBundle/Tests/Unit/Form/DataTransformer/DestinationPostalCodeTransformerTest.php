<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\DestinationPostalCodeTransformer;

class DestinationPostalCodeTransformerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DestinationPostalCodeTransformer */
    private $transformer;

    /** @var ArrayCollection|ShippingMethodsConfigsRuleDestinationPostalCode[] */
    private $postalCodes;

    protected function setUp(): void
    {
        $this->transformer = new DestinationPostalCodeTransformer();

        $this->postalCodes = new ArrayCollection();
        $this->postalCodes->add(
            (new ShippingMethodsConfigsRuleDestinationPostalCode())->setName('123')
        );
        $this->postalCodes->add(
            (new ShippingMethodsConfigsRuleDestinationPostalCode())->setName('753')
        );
    }

    public function testTransform()
    {
        static::assertSame('123, 753', $this->transformer->transform($this->postalCodes));
    }

    public function testTransformOnNull()
    {
        static::assertSame('', $this->transformer->transform(null));
    }

    public function testReverseTransform()
    {
        static::assertEquals($this->postalCodes, $this->transformer->reverseTransform('123, 753'));
    }

    public function testReverseTransformOnEmpty()
    {
        static::assertEquals(new ArrayCollection(), $this->transformer->reverseTransform(''));
        static::assertEquals(new ArrayCollection(), $this->transformer->reverseTransform(null));
    }
}
