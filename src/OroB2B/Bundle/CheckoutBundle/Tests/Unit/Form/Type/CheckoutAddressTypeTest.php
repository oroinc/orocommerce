<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\FormBundle\Form\Extension\AdditionalAttrExtension;

use OroB2B\Bundle\CheckoutBundle\Form\Type\CheckoutAddressType;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\FrontendBundle\Form\Type\CountryType;
use OroB2B\Bundle\FrontendBundle\Form\Type\RegionType;
use OroB2B\Bundle\OrderBundle\Tests\Unit\Form\Type\AbstractOrderAddressTypeTest;

class CheckoutAddressTypeTest extends AbstractOrderAddressTypeTest
{
    protected function initFormType()
    {
        $this->formType = new CheckoutAddressType(
            $this->addressFormatter,
            $this->orderAddressManager,
            $this->orderAddressSecurityProvider,
            $this->serializer
        );
        $this->formType->setDataClass('OroB2B\Bundle\OrderBundle\Entity\OrderAddress');
    }

    public function testGetName()
    {
        $this->assertEquals(CheckoutAddressType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('oro_address', $this->formType->getParent());
    }


    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $ext = parent::getExtensions();
        return array_merge($ext, [new PreloadedExtension(
            [
            'orob2b_country' => new CountryType(),
            'orob2b_region' => new RegionType(),
            ],
            ['form' => [new AdditionalAttrExtension()]]
        )]);
    }

    /**
     * @return Checkout
     */
    protected function getEntity()
    {
        return new Checkout();
    }
}
