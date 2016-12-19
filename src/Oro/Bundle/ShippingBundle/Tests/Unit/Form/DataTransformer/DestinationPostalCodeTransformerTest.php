<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodsConfigsRuleDestinationPostalCode;
use Oro\Bundle\ShippingBundle\Form\DataTransformer\DestinationPostalCodeTransformer;

class DestinationPostalCodeTransformerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DestinationPostalCodeTransformer */
    private $transformer;

    /** @var ArrayCollection|ShippingMethodsConfigsRuleDestinationPostalCode[] */
    private $postalCodes;

    protected function setUp()
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

    public function testReverseTransform()
    {
        static::assertEquals($this->postalCodes, $this->transformer->reverseTransform('123, 753'));
    }
}
