<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Form\Type\ContactInfoManualTextType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ContactInfoManualTextTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ContactInfoManualTextType
     */
    private $formType;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formType = new ContactInfoManualTextType($this->configManager);
        parent::setUp();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ContactInfoManualTextType::class => $this->formType
                ],
                []
            ),
        ];
    }

    public function testConfigOptions()
    {
        $this->configManager->expects(static::once())
            ->method('get')
            ->willReturn(false);

        $expectedOptions = [
            'disabled' => true
        ];

        $form = $this->factory->create(ContactInfoManualTextType::class, null, []);
        $formConfig = $form->getConfig();

        static::assertTrue($formConfig->hasOption('disabled'));
        static::assertEquals($expectedOptions['disabled'], $form->createView()->vars['disabled']);
    }
}
