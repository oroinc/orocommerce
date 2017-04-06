<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Validator\Validation;

use Oro\Bundle\PaymentBundle\Form\Type\CreditCardExpirationDateType;
use Oro\Bundle\PaymentBundle\Form\Type\CreditCardType;

class CreditCardTypeTest extends FormIntegrationTestCase
{
    /**
     * @var CreditCardType
     */
    protected $formType;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->formType = new CreditCardType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    CreditCardExpirationDateType::NAME => new CreditCardExpirationDateType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testFormConfigurationWhenCvvEntryNotRequired()
    {
        $form = $this->factory->create($this->formType, null, ['requireCvvEntryEnabled' => false]);
        $this->assertFalse($form->has('CVV2'));
    }

    public function testFormConfigurationWithoutOptions()
    {
        $form = $this->factory->create($this->formType);
        $this->assertTrue($form->has('CVV2'));
    }

    public function testFormConfigurationWhenCvvEntryRequired()
    {
        $form = $this->factory->create($this->formType, null, ['requireCvvEntryEnabled' => true]);
        $this->assertTrue($form->has('CVV2'));
        $this->assertTrue($form->has('ACCT'));
        $this->assertTrue($form->has('expirationDate'));
    }
}
