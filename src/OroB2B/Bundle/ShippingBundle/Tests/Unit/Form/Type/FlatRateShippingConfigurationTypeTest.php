<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\ShippingBundle\Entity\FlatRateRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Form\Type\FlatRateShippingConfigurationType;
use OroB2B\Bundle\ShippingBundle\Form\Type\ShippingRuleConfigurationType;
use OroB2B\Bundle\ShippingBundle\Method\FlatRateShippingMethod;

class FlatRateShippingConfigurationTypeTest extends FormIntegrationTestCase
{
    /** @var FlatRateShippingConfigurationType */
    protected $formType;

    protected function setUp()
    {
        parent::setUp();
        $roundingService = $this->getMock(RoundingServiceInterface::class);
        $roundingService->expects($this->any())
            ->method('getPrecision')
            ->willReturn(4);
        $roundingService->expects($this->any())
            ->method('getRoundType')
            ->willReturn(RoundingServiceInterface::ROUND_HALF_UP);

        $this->formType = new FlatRateShippingConfigurationType($roundingService);
    }

    public function testGetName()
    {
        $this->assertEquals(FlatRateShippingConfigurationType::NAME, $this->formType->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array|null $data
     */
    public function testSubmit($data)
    {
        $form = $this->factory->create($this->formType, $data);

        $this->assertEquals($data, $form->getData());

        $form->submit([
            'type' => FlatRateShippingMethod::NAME,
            'method' => FlatRateShippingMethod::NAME,
            'value' => '42',
        ]);
        $entity = (new FlatRateRuleConfiguration())
            ->setValue(42)
            ->setMethod(FlatRateShippingMethod::NAME)
            ->setType(FlatRateShippingMethod::NAME);

        $this->assertTrue($form->isValid());
        $this->assertEquals($entity, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [null],
            [
                (new FlatRateRuleConfiguration)
                    ->setMethod('test')
                    ->setType('test')
            ],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    ShippingRuleConfigurationType::NAME => new ShippingRuleConfigurationType()
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }
}
