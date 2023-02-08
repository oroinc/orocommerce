<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Form\Type\ContactInfoManualTextType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;

class ContactInfoManualTextTypeTest extends FormIntegrationTestCase
{
    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var ContactInfoManualTextType */
    private $formType;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formType = new ContactInfoManualTextType($this->configManager);
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

    public function testConfigOptions()
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->willReturn(false);

        $expectedOptions = [
            'disabled' => true
        ];

        $form = $this->factory->create(ContactInfoManualTextType::class, null, []);
        $formConfig = $form->getConfig();

        self::assertTrue($formConfig->hasOption('disabled'));
        self::assertEquals($expectedOptions['disabled'], $form->createView()->vars['disabled']);
    }
}
