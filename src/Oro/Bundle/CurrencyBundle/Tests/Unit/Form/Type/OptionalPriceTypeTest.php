<?php

namespace Oro\Bundle\CurrencyBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Oro\Bundle\CurrencyBundle\Form\Type\PriceType;
use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\CurrencyBundle\Model\OptionalPrice;
use Oro\Bundle\CurrencyBundle\Form\Type\OptionalPriceType;

class OptionalPriceTypeTest extends FormIntegrationTestCase
{
    /**
     * @var OptionalPriceType
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->formType = new OptionalPriceType();
        $this->formType->setDataClass('Oro\Bundle\CurrencyBundle\Model\OptionalPrice');

        parent::setUp();
    }

    public function testSetDefaultOptions()
    {
        /* @var $resolver \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'data_class' => 'Oro\Bundle\CurrencyBundle\Model\OptionalPrice',
                'validation_groups' => ['Optional'],
            ])
        ;

        $this->formType->setDefaultOptions($resolver);
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(OptionalPriceType::NAME, $this->formType->getName());
    }

    /**
     * @param bool $isValid
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($isValid, $submittedData, $expectedData, $defaultData = null)
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
            'valid data' => [
                'isValid'       => true,
                'submittedData' => [
                    'value' => 10,
                    'currency' => 'USD',
                ],
                'expectedData'  => OptionalPrice::create(10, 'USD'),
                'defaultData'   => new OptionalPrice(),
            ],
            'empty value & currency' => [
                'isValid'       => true,
                'submittedData' => [],
                'expectedData'  => null,
                'defaultData'   => new OptionalPrice(),
            ],
            'empty value' => [
                'isValid'       => true,
                'submittedData' => [
                    'currency' => 'USD',
                ],
                'expectedData'  => null,
                'defaultData'   => new OptionalPrice(),
            ],
            'empty currency' => [
                'isValid'       => false,
                'submittedData' => [
                    'value' => 10.0,
                ],
                'expectedData'  => OptionalPrice::create(10.0, null),
                'defaultData'   => new OptionalPrice(),
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $configManager->expects($this->any())
            ->method('get')
            ->with('oro_currency.allowed_currencies')
            ->will($this->returnValue(['USD', 'EUR']));

        $localeSettings = $this->getMockBuilder('Oro\Bundle\LocaleBundle\Model\LocaleSettings')
            ->disableOriginalConstructor()
            ->getMock();

        $currencyPrice = new PriceType();
        $currencyPrice->setDataClass('Oro\Bundle\CurrencyBundle\Model\Price');

        return [
            new PreloadedExtension([
                    CurrencySelectionType::NAME => new CurrencySelectionType($configManager, $localeSettings),
                    $currencyPrice->getName()   => $currencyPrice,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
