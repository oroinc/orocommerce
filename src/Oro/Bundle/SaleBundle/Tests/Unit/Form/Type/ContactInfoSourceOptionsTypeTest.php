<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SaleBundle\Form\Type\ContactInfoSourceOptionsType;
use Oro\Bundle\SaleBundle\Provider\OptionsProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class ContactInfoSourceOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var OptionsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $customerOptionProvider;

    /** @var ContactInfoSourceOptionsType */
    private $formType;

    protected function setUp(): void
    {
        $this->customerOptionProvider = $this->createMock(OptionsProviderInterface::class);
        $this->formType = new ContactInfoSourceOptionsType($this->customerOptionProvider);
        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([$this->formType], [])
        ];
    }

    public function testSubmit()
    {
        $allowedOptions = [
            'option1',
            'option2',
        ];
        $inputOptions = [];
        $submittedData = 'option1';
        $expectedOptions = [
            'choices' => [
                new ChoiceView('option1', 'option1', 'oro.sale.available_customer_options.type.option1.label'),
                new ChoiceView('option2', 'option2', 'oro.sale.available_customer_options.type.option2.label')
            ],
        ];
        $this->customerOptionProvider
            ->method('getOptions')
            ->willReturn($allowedOptions);

        $form = $this->factory->create(ContactInfoSourceOptionsType::class, null, $inputOptions);
        $formConfig = $form->getConfig();

        foreach ($expectedOptions as $key => $value) {
            self::assertTrue($formConfig->hasOption($key));
        }

        self::assertEquals($expectedOptions['choices'], $form->createView()->vars['choices']);
        $form->submit($submittedData);
        self::assertTrue($form->isValid());
        self::assertTrue($form->isSynchronized());
        self::assertEquals($submittedData, $form->getData());
    }
}
