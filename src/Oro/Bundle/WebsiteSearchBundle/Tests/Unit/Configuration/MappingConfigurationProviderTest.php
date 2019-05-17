<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Configuration;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle2\TestBundle2;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle3\TestBundle3;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class MappingConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $eventDispatcher;

    /** @var MappingConfigurationProvider */
    private $configurationProvider;

    protected function setUp()
    {
        $bundle1 = new TestBundle1();
        $bundle2 = new TestBundle2();
        $bundle3 = new TestBundle3();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle2->getName() => get_class($bundle2),
                $bundle1->getName() => get_class($bundle1),
                $bundle3->getName() => get_class($bundle3)
            ]);

        $this->cacheFile = $this->getTempFile('ConfigurationProvider');
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->configurationProvider = new MappingConfigurationProvider(
            $this->cacheFile,
            false,
            $this->eventDispatcher
        );
    }

    public function testGetConfigurationWithCache()
    {
        $cachedConfig = [
            'Acme\Bundle\AcmeBundle\Entity\Test' => [
                'fields' => [
                    'field1' => ['name' => 'field1', 'type' => 'text']
                ]
            ]
        ];
        file_put_contents($this->cacheFile, \sprintf('<?php return %s;', \var_export($cachedConfig, true)));

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->assertEquals($cachedConfig, $this->configurationProvider->getConfiguration());
    }

    public function testGetConfigurationWithoutCache()
    {
        $this->configurationProvider->clearCache();

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(function (string $eventName, WebsiteSearchMappingEvent $event) {
                $this->assertEquals(WebsiteSearchMappingEvent::NAME, $eventName);
                $event->setConfiguration([
                    'Oro\Bundle\TestBundle3\Entity\Product' => [
                        'fields' => [
                            'some_attribute' => [
                                'name' => 'some_attribute',
                                'type' => 'text'
                            ]
                        ]
                    ]
                ]);
            });

        $config = $this->configurationProvider->getConfiguration();

        $expectedConfig = [
            'Oro\Bundle\TestBundle2\Entity\Page'    => [
                'alias'  => 'page_WEBSITE_ID',
                'fields' => [
                    'title_LOCALIZATION_ID'       => ['name' => 'title_LOCALIZATION_ID', 'type' => 'text'],
                    'test_first_repeating_field'  => ['name' => 'test_first_repeating_field', 'type' => 'integer'],
                    'test_second_repeating_field' => ['name' => 'test_second_repeating_field', 'type' => 'integer'],
                    'custom_field'                => ['name' => 'custom_field', 'type' => 'text']
                ]
            ],
            'Oro\Bundle\TestBundle3\Entity\Product' => [
                'alias'  => 'product_WEBSITE_ID',
                'fields' => [
                    'title_LOCALIZATION_ID' => ['name' => 'title_LOCALIZATION_ID', 'type' => 'text'],
                    'price'                 => ['name' => 'price', 'type' => 'decimal'],
                    'some_attribute'        => ['name' => 'some_attribute', 'type' => 'text']
                ]
            ]
        ];

        $this->assertEquals($expectedConfig, $config);
    }
}
