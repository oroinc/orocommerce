<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Entity;

use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ShippingRuleMethodConfigTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    const TYPE_IDENTIFIER = 'flat_rate';
    const TYPE_OPTIONS = [
        'price'        => 12,
        'handling_fee' => null,
        'type'         => 'per_item',
    ];

    /**
     * @var ShippingRuleMethodTypeConfig
     */
    protected $typeConfig;

    /**
     * @var ShippingRuleMethodConfig
     */
    protected $entity;

    public function setUp()
    {
        $this->typeConfig = (new ShippingRuleMethodTypeConfig())
            ->setEnabled(true)
            ->setType(self::TYPE_IDENTIFIER)
            ->setOptions(self::TYPE_OPTIONS);
        $this->entity = new ShippingRuleMethodConfig();
        $this->entity->addTypeConfig($this->typeConfig);
    }

    public function testAccessors()
    {
        $properties = [
            ['id', 1],
            ['method', 'custom'],
            ['options', ['custom' => 'test']],
            ['rule', new ShippingRule()],
        ];

        $entity = new ShippingRuleMethodConfig();

        $this->assertPropertyAccessors($entity, $properties);
        $this->assertPropertyCollection($entity, 'typeConfigs', new ShippingRuleMethodTypeConfig());
    }

    public function testGetOptionsByTypeEmpty()
    {
        $methodTypeWrong = $this->getMockBuilder(ShippingMethodTypeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $methodTypeWrong->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('wrong_type');
        $this->assertEquals([], $this->entity->getOptionsByType($methodTypeWrong));
    }

    public function testGetOptionsByType()
    {
        $methodTypeRight = $this->getMockBuilder(ShippingMethodTypeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $methodTypeRight->expects($this->once())
            ->method('getIdentifier')
            ->willReturn(self::TYPE_IDENTIFIER);
        $this->assertEquals(
            [
                self::TYPE_IDENTIFIER => self::TYPE_OPTIONS
            ],
            $this->entity->getOptionsByTypes([$methodTypeRight])
        );
    }

    public function testGetOptionsByTypesEmpty()
    {
        $methodTypeWrong = $this->getMockBuilder(ShippingMethodTypeInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $methodTypeWrong->expects($this->once())
            ->method('getIdentifier')
            ->willReturn('wrong_type');
        $this->assertEquals([], $this->entity->getOptionsByTypes([$methodTypeWrong]));
    }
}
