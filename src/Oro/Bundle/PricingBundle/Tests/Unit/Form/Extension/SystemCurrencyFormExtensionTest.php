<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencyType;
use Oro\Bundle\CurrencyBundle\Provider\CurrencyProviderInterface;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\PricingBundle\Form\Extension\SystemCurrencyFormExtension;

use Symfony\Bundle\FrameworkBundle\Translation\Translator;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class SystemCurrencyFormExtensionTest extends FormIntegrationTestCase
{
    /**
     * @var LocaleSettings
     */
    protected $localeSettings;

    /**
     * @var Translator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;


    /**
     * @var CurrencyProviderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyProvider;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->translator = $this->getMockBuilder(Translator::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->localeSettings = $this->getMockBuilder(LocaleSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->currencyProvider = $this->getMockBuilder(CurrencyProviderInterface::class)
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
                        $this->localeSettings,
                        $this->translator,
                        $this->currencyProvider
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
            $this->currencyProvider->expects($this->once())
                ->method('getCurrencyList')
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
