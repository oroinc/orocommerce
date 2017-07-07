<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SaleBundle\Form\Type\ContactInfoManualTextType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class ContactInfoManualTextTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ContactInfoManualTextType
     */
    private $formType;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->formType = new ContactInfoManualTextType($this->configManager);
        parent::setUp();
    }

    public function testConfigOptions()
    {
        $this->configManager->expects(static::once())
            ->method('get')
            ->willReturn(false);

        $expectedOptions = [
            'disabled' => true
        ];

        $form = $this->factory->create($this->formType, null, []);
        $formConfig = $form->getConfig();

        static::assertTrue($formConfig->hasOption('disabled'));
        static::assertEquals($expectedOptions['disabled'], $form->createView()->vars['disabled']);
    }
}
