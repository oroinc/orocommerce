<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Form\Type;

use Oro\Bundle\PayPalBundle\Form\Type\CreditCardExpirationDateType;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\Validator\Validation;

class CreditCardTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension(
                [
                    CreditCardExpirationDateType::class => new CreditCardExpirationDateType(),
                ],
                []
            ),
            new ValidatorExtension(Validation::createValidator()),
        ];
    }

    public function testFormConfigurationWhenCvvEntryNotRequired()
    {
        $form = $this->factory->create(CreditCardType::class, null, ['requireCvvEntryEnabled' => false]);
        $this->assertFalse($form->has('CVV2'));
        $this->assertFalse($form->has('save_for_later'));
    }

    public function testFormConfigurationWithoutOptions()
    {
        $form = $this->factory->create(CreditCardType::class);
        $this->assertTrue($form->has('CVV2'));
        $this->assertFalse($form->has('save_for_later'));
    }

    public function testFormConfigurationWhenCvvEntryRequired()
    {
        $form = $this->factory->create(CreditCardType::class, null, ['requireCvvEntryEnabled' => true]);
        $this->assertTrue($form->has('CVV2'));
        $this->assertTrue($form->has('ACCT'));
        $this->assertTrue($form->has('expirationDate'));
        $this->assertTrue($form->has('EXPDATE'));
    }

    public function testSafeForLaterFieldWithZeroAmountAuthorizationEnabledOption()
    {
        $form = $this->factory->create(CreditCardType::class, null, ['zeroAmountAuthorizationEnabled' => true]);
        $this->assertTrue($form->has('save_for_later'));
    }

    public function testSafeForLaterFieldWithZeroAmountAuthorizationEnabledOptionDisabled()
    {
        $form = $this->factory->create(CreditCardType::class, null, ['zeroAmountAuthorizationEnabled' => false]);
        $this->assertFalse($form->has('save_for_later'));
    }

    public function testFinishView()
    {
        $form = $this->createMock(FormInterface::class);

        $formView = new FormView();
        $formChildrenView = new FormView();
        $formChildrenView->vars = [
            'full_name' => 'full_name',
            'name' => 'name',
        ];
        $formView->children = [$formChildrenView];

        $formType = new CreditCardType();
        $formType->finishView($formView, $form, []);

        foreach ($formView->children as $formItemData) {
            $this->assertEquals('name', $formItemData->vars['full_name']);
        }
    }
}
