<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\Price;
use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;

class PriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var PriceType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new PriceType();
        $this->formType->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /* @var $configManager \PHPUnit_Framework_MockObject_MockObject|ConfigManager */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        /* @var $localeSettings \PHPUnit_Framework_MockObject_MockObject|LocaleSettings */
        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings)],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }

    /**
     * @param bool $isValid
     * @param mixed $defaultData
     * @param array $submittedData
     * @param mixed $expectedData
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $defaultData, $submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType, $defaultData, []);

        $this->assertEquals($defaultData, $form->getData());
        $form->submit($submittedData);
        $this->assertEquals($isValid, $form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'price without value' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [],
                'expectedData'  => null
            ],
            'not numeric value' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 'test-value',
                    'currency' => 'USD'
                ],
                'expectedData'  => null,
            ],
            'value < 0' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => -1,
                    'currency' => 'USD'
                ],
                'expectedData'  => (new Price())->setValue(-1)->setCurrency('USD')
            ],
            'price without currency' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100
                ],
                'expectedData'  => (new Price())->setValue(100)
            ],
            'invalid currency' => [
                'isValid'       => false,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100,
                    'currency' => 'UAH'
                ],
                'expectedData'  => (new Price())->setValue(100)
            ],
            'price with value' => [
                'isValid'       => true,
                'defaultData'   => new Price(),
                'submittedData' => [
                    'value' => 100,
                    'currency' => 'USD'
                ],
                'expectedData'  => (new Price())->setValue(100)->setCurrency('USD')
            ]
        ];
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(PriceType::NAME, $this->formType->getName());
    }
}
