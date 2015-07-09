<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\PricingBundle\Form\Type\TierProductPriceType;

class TierProductPriceTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TierProductPriceType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new TierProductPriceType();
    }

    protected function tearDown()
    {
        unset($this->type);
    }

    public function testGetName()
    {
        $this->assertInternalType('string', $this->type->getName());
        $this->assertEquals(TierProductPriceType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertInternalType('string', $this->type->getParent());
        $this->assertEquals('checkbox', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');

        $resolver->expects($this->once())->method('setDefaults')->with(
            $this->logicalAnd(
                $this->isType('array'),
                $this->arrayHasKey('label'),
                $this->contains('orob2b.pricing.productprice.tier_price.label'),
                $this->arrayHasKey('required'),
                $this->contains(false)
            )
        );

        $this->type->setDefaultOptions($resolver);
    }
}
