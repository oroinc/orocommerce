<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Form\Type\ContactInfoUserOptionsType;
use Oro\Bundle\SaleBundle\Provider\OptionProviderWithDefaultValueInterface;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\ChoiceList\View\ChoiceView;

class ContactInfoUserOptionsTypeTest extends FormIntegrationTestCase
{
    /** @var OptionProviderWithDefaultValueInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $optionProvider;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ContactInfoUserOptionsType */
    private $formType;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->optionProvider = $this->createMock(OptionProviderWithDefaultValueInterface::class);
        $this->formType = new ContactInfoUserOptionsType($this->optionProvider, $this->configManager);
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
        $this->configManager->expects(self::once())
            ->method('get')
            ->willReturn('');
        $this->optionProvider->expects($this->once())
            ->method('getDefaultOption')
            ->willReturn('option1');
        $allowedOptions = [
            'option1',
            'option2',
        ];
        $inputOptions = [];
        $submittedData = 'option1';
        $expectedOptions = [
            'choices' => [
                new ChoiceView('option1', 'option1', 'oro.sale.contact_info_user_options.type.option1.label'),
                new ChoiceView('option2', 'option2', 'oro.sale.contact_info_user_options.type.option2.label')
            ],
        ];
        $this->optionProvider->expects(self::any())
            ->method('getOptions')
            ->willReturn($allowedOptions);

        $form = $this->factory->create(ContactInfoUserOptionsType::class, null, $inputOptions);
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
