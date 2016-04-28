<?php

namespace OroB2B\Bundle\WebsiteBundle\Tests\Unit\Translation\CacheWarmer;

use Symfony\Bundle\FrameworkBundle\CacheWarmer\TranslationsCacheWarmer;

use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface;
use Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyProvider;

use OroB2B\Bundle\WebsiteBundle\Translation\CacheWarmer\CompositeTranslationCacheWarmer;

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
     * @var TranslationStrategyInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mixingStrategy;

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

        $this->mixingStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');

        $this->warmer = new CompositeTranslationCacheWarmer(
            $this->innerWarmer,
            $this->strategyProvider,
            $this->mixingStrategy
        );
    }

    public function testWarmUp()
    {
        $directory = '/cache_dir';
        $defaultStrategyName = 'default';
        $mixingStrategyName = 'mix';

        $defaultStrategy = $this->getMock('Oro\Bundle\TranslationBundle\Strategy\TranslationStrategyInterface');
        $defaultStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($defaultStrategyName);

        $this->mixingStrategy->expects($this->any())
            ->method('getName')
            ->willReturn($mixingStrategyName);

        $currentStrategy = $defaultStrategy;
        $calledStrategies = [];

        $this->strategyProvider->expects($this->once())
            ->method('getStrategy')
            ->willReturn($defaultStrategy);
        $this->strategyProvider->expects($this->exactly(2))
            ->method('setStrategy')
            ->willReturnCallback(
                function (TranslationStrategyInterface $strategy) use (&$currentStrategy) {
                    $currentStrategy = $strategy;
                }
            );
        $this->innerWarmer->expects($this->exactly(2))
            ->method('warmUp')
            ->with($directory)
            ->willReturnCallback(
                function () use (&$currentStrategy, &$calledStrategies) {
                    /** @var TranslationStrategyInterface $currentStrategy */
                    $this->assertNotEmpty($currentStrategy);
                    $calledStrategies[] = $currentStrategy->getName();
                }
            );

        $this->warmer->warmUp($directory);
        $this->assertEquals([$mixingStrategyName, $defaultStrategyName], $calledStrategies);
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
}
