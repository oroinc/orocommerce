<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ShippingBundle\Entity\LengthUnit;
use OroB2B\Bundle\ShippingBundle\Form\Type\DimensionsType;
use OroB2B\Bundle\ShippingBundle\Model\Dimensions;
use OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider;

class DimensionsTypeTest extends FormIntegrationTestCase
{
    const DATA_CLASS = 'OroB2B\Bundle\ShippingBundle\Model\Dimensions';

    /**
     * @var DimensionsType
     */
    protected $formType;

    /**
     * @var AbstractMeasureUnitProvider
     */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();

        $this->formType = new DimensionsType($this->initProvider());
        $this->formType->setDataClass(self::DATA_CLASS);
    }

    /**
     * @return AbstractMeasureUnitProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function initProvider()
    {
        $this->provider = $this->getMockBuilder('OroB2B\Bundle\ShippingBundle\Provider\AbstractMeasureUnitProvider')
            ->disableOriginalConstructor()
            ->getMock();

        return $this->provider;
    }

    public function testConfigureOptions()
    {
        /* @var $resolver OptionsResolver|\PHPUnit_Framework_MockObject_MockObject */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'data_class' => self::DATA_CLASS,
                    'compact' => false,
                ]
            );

        $this->formType->configureOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals(DimensionsType::NAME, $this->formType->getName());
    }

    /**
     * @param array $submittedData
     * @param mixed $expectedData
     * @param mixed $defaultData
     *
     * @dataProvider submitProvider
     */
    public function testSubmit($submittedData, $expectedData, $defaultData = null)
    {
        $form = $this->factory->create($this->formType, $defaultData);

        $this->assertEquals($defaultData, $form->getData());

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitProvider()
    {
        return [
            'full data' => [
                'submittedData' => [
                    'length' => '2',
                    'width' => '4',
                    'height' => '6',
                    'unit' => 'm',
                ],
                'expectedData' => $this->getDimensions($this->getLengthUnit('m'), 2, 4, 6),
                'defaultData' => $this->getDimensions($this->getLengthUnit('sm'), 1, 3, 5),
            ],
        ];
    }

    /**
     * @param string $code
     *
     * @return LengthUnit
     */
    protected function getLengthUnit($code)
    {
        $lengthUnit = new LengthUnit();
        $lengthUnit->setCode($code);

        return $lengthUnit;
    }

    /**
     * @param LengthUnit $lengthUnit
     * @param float $length
     * @param float $width
     * @param float $height
     *
     * @return Dimensions
     */
    protected function getDimensions(LengthUnit $lengthUnit, $length, $width, $height)
    {
        return Dimensions::create($length, $width, $height, $lengthUnit);
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    DimensionsType::NAME => new DimensionsType($this->initProvider()),
                    'entity' => new EntityType(['m' => $this->getLengthUnit('m')])
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
