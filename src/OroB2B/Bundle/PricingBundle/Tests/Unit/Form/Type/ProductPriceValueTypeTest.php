<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Test\FormIntegrationTestCase;

use OroB2B\Bundle\PricingBundle\Form\Type\ProductPriceValueType;
use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;

class ProductPriceValueTypeTest extends FormIntegrationTestCase
{
    /** @var ProductPriceValueType */
    protected $formType;

    /**
     * @var RoundingServiceInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $roundingService;

    protected function setUp()
    {
        $this->roundingService = $this->getMock('OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface');
        $this->roundingService->expects($this->any())
            ->method('round')
            ->willReturnCallback(
                function ($value, $precision) {
                    return round($value, $precision);
                }
            );

        $this->formType = new ProductPriceValueType($this->roundingService);

        parent::setUp();
    }

    /**
     * @param string $submittedData
     * @param string $expectedData
     * @dataProvider submitDataProvider
     */
    public function testSubmit($submittedData, $expectedData)
    {
        $form = $this->factory->create($this->formType);

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());
        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'not changed without typecast' => ['101.0001', '101.0001'],
            'not changed' => [101.0001, '101.0001'],
            'changed' => [101.0002, '101.0002'],
            'round typecast' => [101.00025555555, '101.0003'],
            'round' => ['101.00025555555', '101.0003'],
            'fill zeros' => [101.2000, '101.2'], // @todo: should fail https://magecore.atlassian.net/browse/BB-1621
        ];
    }
}
