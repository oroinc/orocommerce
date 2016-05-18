<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Form;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\CheckoutBundle\Form\Type\ShipToBillingAddressType;

class ShipToBillingAddressTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
    }

    public function testSubmitParentFormWhenChecked()
    {
        $form = $this->createParentForm();

        $form->submit([
            ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD => 'address',
            'test_type' => 1
        ]);

        $this->assertFalse($form->has(ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD));
    }

    public function testSubmitWhenAddressFormIsNotPresent()
    {
        $form = $this->factory->createBuilder()
            ->add('test_type', ShipToBillingAddressType::NAME)
            ->getForm();

        $form->submit(['test_type' => 1]);
        $this->assertFalse($form->has(ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD));
    }

    public function testSubmitParentFormWhenNotChecked()
    {
        $form = $this->createParentForm();

        $form->submit([
            ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD => 'address',
            'test_type' => 0
        ]);

        $this->assertTrue($form->has(ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD));
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [ShipToBillingAddressType::NAME => new ShipToBillingAddressType()],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @return Form
     */
    protected function createParentForm()
    {
        $form = $this->factory->createBuilder()
            ->add('test_type', ShipToBillingAddressType::NAME)
            ->add(ShipToBillingAddressType::SHIPPING_ADDRESS_FORM_FIELD, 'text')
            ->getForm();

        return $form;
    }
}
