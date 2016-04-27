<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Translation\Strategy;

use Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy;

use OroB2B\Bundle\FrontendBundle\Request\FrontendHelper;
use OroB2B\Bundle\WebsiteBundle\Translation\Strategy\CompositeFallbackStrategy;

class CompositeFallbackStrategyTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendHelper;

    /**
     * @var DefaultTranslationStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendStrategy;

    /**
     * @var DefaultTranslationStrategy|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $backendStrategy;

    /**
     * @var CompositeFallbackStrategy
     */
    protected $strategy;

    protected function setUp()
    {
        $this->frontendHelper = $this->getMockBuilder('OroB2B\Bundle\FrontendBundle\Request\FrontendHelper')
            ->disableOriginalConstructor()->getMock();
        $this->frontendStrategy = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy')
            ->disableOriginalConstructor()->getMock();
        $this->backendStrategy = $this
            ->getMockBuilder('Oro\Bundle\TranslationBundle\Strategy\DefaultTranslationStrategy')
            ->disableOriginalConstructor()->getMock();
        $this->strategy = new CompositeFallbackStrategy(
            $this->frontendHelper,
            $this->frontendStrategy,
            $this->backendStrategy
        );
    }

    /**
     * @dataProvider strategiesDataProvider
     *
     * @param bool $isFrontend
     * @param string $activeStrategy
     * @param string $inactiveStrategy
     */
    public function testGetLocaleFallbacks($isFrontend, $activeStrategy, $inactiveStrategy)
    {
        $locales = [
            'en' => ['en_EN' => ['en_FR' => []]],
            'ru' => ['ru_RU' => []],
        ];
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontend);
        $this->{$activeStrategy}->expects($this->once())
            ->method('getLocaleFallbacks')
            ->willReturn($locales);
        $this->{$inactiveStrategy}->expects($this->never())
            ->method('getLocaleFallbacks');
        $this->assertEquals($locales, $this->strategy->getLocaleFallbacks());
    }

    /**
     * @dataProvider strategiesDataProvider
     *
     * @param bool $isFrontend
     * @param string $activeStrategy
     * @param string $inactiveStrategy
     */
    public function testGetName($isFrontend, $activeStrategy, $inactiveStrategy)
    {
        $name = 'strategy_name';
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontend);
        $this->{$activeStrategy}->expects($this->once())
            ->method('getName')
            ->willReturn($name);
        $this->{$inactiveStrategy}->expects($this->never())
            ->method('getName');
        $this->assertEquals($name, $this->strategy->getName());
    }

    /**
     * @return array
     */
    public function strategiesDataProvider()
    {
        return [
            [
                'isFrontend' => true,
                'activeStrategy' => 'frontendStrategy',
                'inactiveStrategy' => 'backendStrategy',
            ],
            [
                'isFrontend' => false,
                'activeStrategy' => 'backendStrategy',
                'inactiveStrategy' => 'frontendStrategy',
            ]
        ];
    }
}
