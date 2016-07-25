<?php

namespace OroB2B\Bundle\MenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use OroB2B\Bundle\MenuBundle\Menu\BuilderInterface;
use OroB2B\Bundle\MenuBundle\Menu\Condition\ConditionExtension;

class ConditionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ConditionExtension
     */
    protected $conditionExtension;

    public function setUp()
    {
        $this->conditionExtension = new ConditionExtension();
    }

    /**
     * @dataProvider buildOptionsDataProvider
     * @param string $condition
     * @param bool $isAllowed
     */
    public function testBuildOptions($condition, $isAllowed)
    {
        $options = [
            'extras' => [
                ConditionExtension::CONDITION_KEY => $condition,
            ],
        ];
        $options = $this->conditionExtension->buildOptions($options);
        $this->assertArrayHasKey(BuilderInterface::IS_ALLOWED_OPTION_KEY, $options['extras']);
        $this->assertEquals($isAllowed, $options['extras'][BuilderInterface::IS_ALLOWED_OPTION_KEY]);
    }

    /**
     * @return array
     */
    public function buildOptionsDataProvider()
    {
        return [
            [
                'condition' => '1 + 2',
                'expectedData' => true,
            ],
            [
                'condition' => '1 > 2',
                'expectedData' => false,
            ]
        ];
    }

    /**
     * @dataProvider buildOptionsEmptyConditionDataProvider
     * @param string $condition
     */
    public function testBuildOptionsEmptyCondition($condition)
    {
        $options = [
            'extras' => [
                ConditionExtension::CONDITION_KEY => $condition,
            ],
        ];
        $options = $this->conditionExtension->buildOptions($options);
        $this->assertArrayNotHasKey(BuilderInterface::IS_ALLOWED_OPTION_KEY, $options['extras']);
    }

    /**
     * @return array
     */
    public function buildOptionsEmptyConditionDataProvider()
    {
        return [
            ['condition' => ''],
            ['condition' => null]
        ];
    }

    public function testBuildOptionsAlreadyProcessed()
    {
        $options = [
            'extras' => [
                BuilderInterface::IS_ALLOWED_OPTION_KEY => !ConditionExtension::DEFAULT_IS_ALLOWED_POLICY,
            ],
        ];
        $processedOptions = $this->conditionExtension->buildOptions($options);

        $this->assertEquals($options, $processedOptions);
    }

    public function testAddProvider()
    {
        /** @var ExpressionFunctionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMock('Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface');
        $options = [
            'extras' => [
                ConditionExtension::CONDITION_KEY => '1 > 0',
            ],
        ];
        $provider->expects($this->once())
            ->method('getFunctions')
            ->willReturn([]);
        $this->conditionExtension->addProvider($provider);
        $this->conditionExtension->addProvider($provider);
        $this->conditionExtension->buildOptions($options);
    }
}
