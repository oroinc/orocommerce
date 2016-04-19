<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\FormBundle\Form\Type\OroDateTimeType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceListSchedule;
use OroB2B\Bundle\PricingBundle\Form\Type\PriceListScheduleType;

class PriceListScheduleTypeTest extends FormIntegrationTestCase
{
    /**
     * @var FormInterface
     */
    protected $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->formType = new PriceListScheduleType();
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        return [
            new PreloadedExtension(
                [
                    OroDateTimeType::NAME => new OroDateTimeType()
                ],
                []
            )
        ];
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param array $submittedData
     * @param PriceListSchedule $expected
     * @param PriceListSchedule|null $data
     */
    public function testSubmit(
        array $submittedData,
        PriceListSchedule $expected,
        PriceListSchedule $data = null
    ) {
        if (!$data) {
            $data = new PriceListSchedule();
        }
        $form = $this->factory->create($this->formType, $data);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $data = $form->getData();
        $this->assertEquals($expected, $data);
    }

    /**
     * todo validation cases in next ticket
     *
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            [
                'submittedData' => [],
                'expected' => new PriceListSchedule()
            ]
        ];
    }
}
