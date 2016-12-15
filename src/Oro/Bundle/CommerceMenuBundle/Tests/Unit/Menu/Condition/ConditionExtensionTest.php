<?php

namespace Oro\Bundle\CommerceMenuBundle\Tests\Unit\Menu\Condition;

use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

use Oro\Bundle\CommerceMenuBundle\Menu\Condition\ConditionExtension;

class ConditionExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConditionExtension */
    private $conditionExtension;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        $this->conditionExtension = new ConditionExtension();
    }

    /**
     * @dataProvider buildOptionsDataProvider
     *
     * @param string $condition
     * @param bool   $isAllowed
     */
    public function testBuildOptions($condition, $isAllowed)
    {
        $options = [
            'extras' => [
                ConditionExtension::CONDITION_KEY => $condition,
            ],
        ];
        $options = $this->conditionExtension->buildOptions($options);
        $this->assertArrayHasKey(ConditionExtension::IS_ALLOWED_OPTION_KEY, $options['extras']);
        $this->assertEquals($isAllowed, $options['extras'][ConditionExtension::IS_ALLOWED_OPTION_KEY]);
    }

    /**
     * @return array
     */
    public function buildOptionsDataProvider()
    {
        return [
            'positive condition' => [
                'condition' => '1 + 2',
                'expectedData' => true,
            ],
            'negative condition' => [
                'condition' => '1 > 2',
                'expectedData' => false,
            ]
        ];
    }

    /**
     * @dataProvider buildOptionsEmptyConditionDataProvider
     *
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
        $this->assertArrayNotHasKey(ConditionExtension::IS_ALLOWED_OPTION_KEY, $options['extras']);
    }

    /**
     * @return array
     */
    public function buildOptionsEmptyConditionDataProvider()
    {
        return [
            'empty condition' => ['condition' => ''],
            'null condition' => ['condition' => null]
        ];
    }

    public function testBuildOptionsAlreadyProcessed()
    {
        $options = [
            'extras' => [
                ConditionExtension::IS_ALLOWED_OPTION_KEY => !ConditionExtension::DEFAULT_IS_ALLOWED_POLICY,
            ],
        ];
        $processedOptions = $this->conditionExtension->buildOptions($options);

        $this->assertEquals($options, $processedOptions);
    }

    public function testAddProvider()
    {
        /** @var ExpressionFunctionProviderInterface|\PHPUnit_Framework_MockObject_MockObject $provider */
        $provider = $this->getMockBuilder(ExpressionFunctionProviderInterface::class)->getMock();

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
