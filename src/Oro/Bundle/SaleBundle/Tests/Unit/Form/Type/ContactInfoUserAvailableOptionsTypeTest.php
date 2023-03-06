<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\SaleBundle\Form\Type\ContactInfoUserAvailableOptionsType;
use Oro\Bundle\SaleBundle\Provider\ContactInfoSourceOptionsProvider;
use Oro\Bundle\SaleBundle\Provider\OptionsProviderInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class ContactInfoUserAvailableOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var ContactInfoSourceOptionsProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $optionProvider;

    /** @var ContactInfoUserAvailableOptionsType */
    private $formType;

    protected function setUp(): void
    {
        $this->optionProvider = $this->createMock(OptionsProviderInterface::class);
        $this->formType = new ContactInfoUserAvailableOptionsType($this->optionProvider);
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
        $submittedData = ['option1', 'option2'];
        $expectedOptions = [
            'choices' => [
                new ChoiceView('option1', 'option1', 'oro.sale.available_user_options.type.option1.label'),
                new ChoiceView('option2', 'option2', 'oro.sale.available_user_options.type.option2.label')
            ],
        ];
        $this->optionProvider->expects(self::any())
            ->method('getOptions')
            ->willReturn($allowedOptions);

        $form = $this->factory->create(ContactInfoUserAvailableOptionsType::class, null, $inputOptions);
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
