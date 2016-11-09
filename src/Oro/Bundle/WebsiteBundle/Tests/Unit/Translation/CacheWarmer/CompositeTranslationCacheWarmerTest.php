<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Unit\Translation\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;
use Oro\Bundle\WebsiteBundle\Translation\CacheWarmer\CompositeTranslationCacheWarmer;

class CompositeTranslationCacheWarmerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslationsCacheWarmer|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $innerWarmer;

    /**
     * @var TranslationStrategyProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $strategyProvider;

    /**
     * @var CompositeTranslationCacheWarmer
     */
    protected $warmer;

    protected function setUp()
    {
        $this->innerWarmer =
            $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer')
                ->disableOriginalConstructor()
                ->getMock();

        $this->strategyProvider =
            $this->getMockBuilder('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider')
                ->disableOriginalConstructor()
                ->getMock();

        $this->warmer = new CompositeTranslationCacheWarmer($this->innerWarmer, $this->strategyProvider);
    }

    public function testWarmUp()
    {
        $directory = '/cache_dir';
        $defaultStrategyName = 'default';
        $mixingStrategyName = 'mix';

        $defaultStrategy = $this->getStrategy($defaultStrategyName);
        $mixingStrategy = $this->getStrategy($mixingStrategyName);

        $this->strategyProvider->expects($this->at(0))->method('getStrategies')
            ->willReturn(['default' => $defaultStrategy, 'mix' => $mixingStrategy]);

        $this->strategyProvider->expects($this->at(1))->method('selectStrategy')->with($defaultStrategyName);
        $this->strategyProvider->expects($this->at(2))->method('selectStrategy')->with($mixingStrategyName);

        $this->strategyProvider->expects($this->at(3))->method('resetStrategy');

        $this->innerWarmer->expects($this->exactly(2))->method('warmUp')->with($directory);

        $this->warmer->warmUp($directory);
    }

    /**
     * @param bool $isOptional
     * @dataProvider optionalDataProvider
     */
    public function testIsOptional($isOptional)
    {
        $this->innerWarmer->expects($this->once())
            ->method('isOptional')
            ->willReturn($isOptional);

        $this->assertEquals($isOptional, $this->warmer->isOptional());
    }

    public function optionalDataProvider()
    {
        return [
            'optional' => [true],
            'not optional' => [false],
        ];
    }

    /**
     * @param string $name
     * @return TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getStrategy($name)
    {
        $strategy = $this->getMock(TranslationStrategyInterface::class);
        $strategy->expects($this->any())->method('isApplicable')->willReturn(true);
        $strategy->expects($this->any())->method('getName')->willReturn($name);

        return $strategy;
    }
}
