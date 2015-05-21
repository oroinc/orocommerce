<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Rounding;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingService;

class RoundingServiceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RoundingService
     */
    protected $service;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager', ['get'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->service = new RoundingService($this->configManager);
    }

    /**
     * @dataProvider roundProvider
     *
     * @param string $roundingType
     * @param float|integer $value
     * @param integer $precision
     * @param float|integer $expectedValue
     */
    public function testRound($roundingType, $value, $precision, $expectedValue)
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('orob2b_product.unit_rounding_type')
            ->willReturn($roundingType);

        $this->assertEquals($expectedValue, $this->service->round($value, $precision));
    }

    /**
     * @return array
     */
    public function roundProvider()
    {
        return [
            'half_up more then half fraction' => [
                'roundingType' => RoundingService::HALF_UP,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'half_up less then half fraction' => [
                'roundingType' => RoundingService::HALF_UP,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down more then half fraction' => [
                'roundingType' => RoundingService::HALF_DOWN,
                'value' => 5.5555,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'half_down less then half fraction' => [
                'roundingType' => RoundingService::HALF_DOWN,
                'value' => 5.5554,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'ceil less then half fraction' => [
                'roundingType' => RoundingService::CEIL,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'ceil more then half fraction' => [
                'roundingType' => RoundingService::CEIL,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.556,
            ],
            'floor less then half fraction' => [
                'roundingType' => RoundingService::FLOOR,
                'value' => 5.5551,
                'precision' => 3,
                'expectedValue' => 5.555,
            ],
            'floor more then half fraction' => [
                'roundingType' => RoundingService::FLOOR,
                'value' => 5.5559,
                'precision' => 3,
                'expectedValue' => 5.555,
            ]
        ];
    }

    /**
     * @throws \OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException
     */
    public function testInvalidRoundingTypeException()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('orob2b_product.unit_rounding_type')
            ->willReturn('test');

        $this->setExpectedException(
            '\OroB2B\Bundle\ProductBundle\Exception\InvalidRoundingTypeException',
            'The type of the rounding is not valid. Allowed the following types: half_up, half_down, ceil, floor.'
        );

        $this->service->round(1.15, 1);
    }
}
