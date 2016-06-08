<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Bundle\FrameworkBundle\Translation\Translator;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\LocaleBundle\Form\Type\CurrencyType;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;

use OroB2B\Bundle\PricingBundle\Form\Extension\SystemCurrencyFormExtension;

class SystemCurrencyFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->translator = $this->getMockBuilder('Oro\Bundle\TranslationBundle\Translation\Translator')
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    'oro_currency' => new CurrencyType(),
                ],
                [
                    'form' => [new SystemCurrencyFormExtension(
                        $this->configManager,
                        $this->localeSettings,
                        $this->translator
                    )],
                ]
            ),
        ];
    }

    /**
     * @dataProvider submitDataProvider
     * @param array $options
     * @param array $submittedData
     * @param array|string $enabledCurrencies
     * @param bool $isValid
     */
    public function testSubmitForm(array $options, $submittedData, array $enabledCurrencies, $isValid)
    {
        $form = $this->factory->create('oro_currency', null, $options);

        if ($options['restrict']) {
            $this->configManager->expects($this->once())
                ->method('get')
                ->with('oro_b2b_pricing.enabled_currencies')
                ->willReturn($enabledCurrencies);
        }

        $form->submit($submittedData);
        $this->assertSame($isValid, $form->isValid());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'multiple without restrict option' => [
                'options' => ['restrict' => false, 'multiple' => true],
                'submittedData' => ['USD', 'CAD', 'EUR'],
                'enabledCurrencies' => ['USD', 'CAD', 'EUR'],
                'isValid' => true
            ],
            'multiple without enabled currencies' => [
                'options' => ['restrict' => false, 'multiple' => true],
                'submittedData' => ['USD', 'CAD', 'EUR'],
                'enabledCurrencies' => [],
                'isValid' => true
            ],
            'multiple restrict valid form' => [
                'options' => ['restrict' => true, 'multiple' => true],
                'submittedData' => ['USD', 'CAD', 'EUR'],
                'enabledCurrencies' => ['USD', 'CAD', 'EUR'],
                'isValid' => true
            ],
            'multiple restrict invalid form' => [
                'options' => ['restrict' => true, 'multiple' => true],
                'submittedData' => ['USD', 'CAD'],
                'enabledCurrencies' => ['USD', 'CAD', 'EUR'],
                'isValid' => false
            ],
            'single restrict valid form' => [
                'options' => ['restrict' => true, 'multiple' => false],
                'submittedData' => 'USD',
                'enabledCurrencies' => ['USD'],
                'isValid' => true
            ],
            'single restrict invalid form' => [
                'options' => ['restrict' => true, 'multiple' => false],
                'submittedData' => 'USD',
                'enabledCurrencies' => ['USD', 'CAD'],
                'isValid' => false
            ],
        ];
    }
}
