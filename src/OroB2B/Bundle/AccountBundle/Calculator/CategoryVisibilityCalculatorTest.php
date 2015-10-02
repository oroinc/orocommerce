<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Calculator;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\AccountBundle\Calculator\CategoryVisibilityCalculator;
use OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility;

class CategoryVisibilityCalculatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryVisibilityCalculator
     */
    protected $calculator;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    public function setUp()
    {
        /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject $managerRegistry */
        $managerRegistry = $this->getMock('\Doctrine\Common\Persistence\ManagerRegistry');
        $this->configManager = $this->getMockBuilder('\Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->calculator = new CategoryVisibilityCalculator($managerRegistry, $this->configManager);
    }

    /**
     * @dataProvider calculateVisibleDataProvider
     *
     * @param array $expected
     * @param array $visibilities
     * @param string $configValue
     */
    public function testCalculateVisible($expected, $visibilities, $configValue)
    {
        $this->configManager->expects($this->any())
            ->method('get')
            ->with(
                CategoryVisibilityCalculator::CONFIG_VALUE_KEY,
                CategoryVisibility::VISIBLE
            )
            ->willReturn($configValue);

        $actual = $this->calculator->calculateVisible($visibilities);
        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function calculateVisibleDataProvider()
    {
        return [
            'empty' => [[], [], CategoryVisibility::VISIBLE],
            'only config value' => [
                [1],
                [
                    [
                        'id' => 1,
                        'parent_id' => null,
                        'to_all' => null,
                        'to_group' => null,
                        'to_account' => null
                    ]
                ],
                CategoryVisibility::VISIBLE
            ],
            'only config value with parent' => [
                [2],
                [
                    [
                        'id' => 1,
                        'parent_id' => null,
                        'to_all' => CategoryVisibility::VISIBLE,
                        'to_group' => null,
                        'to_account' => CategoryVisibility::HIDDEN
                    ],
                    [
                        'id' => 2,
                        'parent_id' => 1,
                        'to_all' => null,
                        'to_group' => null,
                        'to_account' => null
                    ]
                ],
                CategoryVisibility::VISIBLE
            ],

        ];
    }
}
