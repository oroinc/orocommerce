<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Tests\Unit\Provider\AbstractSearchMappingProviderTest;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class WebsiteSearchMappingProviderTest extends AbstractSearchMappingProviderTest
{
    /** @var array */
    protected $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias'  => 'test_entity',
            'fields' => [
                [
                    'name' => 'firstname',
                    'type' => 'text',
                    'store' => true
                ],
                [
                    'name' => 'qty',
                    'type' => 'integer',
                    'store' => true
                ]
            ]
        ]
    ];

    /** @var ConfigurationLoaderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $mappingConfigurationLoader;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $eventDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testGetMappingConfig()
    {
        $provider = $this->getProvider();

        $this->assertEquals(
            [
                'Oro\TestBundle\Entity\TestEntity' => [
                    'alias'  => 'test_entity',
                    'fields' => [
                        'firstname' => [
                            'name' => 'firstname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'qty' => [
                            'name' => 'qty',
                            'type' => 'integer',
                            'store' => true
                        ]
                    ]
                ]
            ],
            $provider->getMappingConfig()
        );

        // Check that cache was used
        $provider->getMappingConfig();
    }

    public function testGetMappingConfigWithEvent()
    {
        $provider = $this->getProvider();

        $this->eventDispatcher->expects($this->exactly(2))
            ->method('dispatch')
            ->with(WebsiteSearchMappingEvent::NAME, $this->isInstanceOf(WebsiteSearchMappingEvent::class))
            ->willReturnOnConsecutiveCalls(
                $this->returnCallback(
                    function ($name, WebsiteSearchMappingEvent $event) {
                        $config = $event->getConfiguration();
                        $config['Oro\TestBundle\Entity\TestEntity']['fields']['lastname'] = [
                            'name' => 'lastname',
                            'type' => 'text',
                            'store' => true
                        ];

                        $event->setConfiguration($config);
                    }
                ),
                $this->returnCallback(
                    function ($name, WebsiteSearchMappingEvent $event) {
                        $config = $event->getConfiguration();
                        $config['Oro\TestBundle\Entity\TestEntity']['fields']['email'] = [
                            'name' => 'email',
                            'type' => 'text',
                            'store' => true
                        ];

                        $event->setConfiguration($config);
                    }
                )
            );

        $this->assertEquals(
            [
                'Oro\TestBundle\Entity\TestEntity' => [
                    'alias'  => 'test_entity',
                    'fields' => [
                        'firstname' => [
                            'name' => 'firstname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'qty' => [
                            'name' => 'qty',
                            'type' => 'integer',
                            'store' => true
                        ],
                        'lastname' => [
                            'name' => 'lastname',
                            'type' => 'text',
                            'store' => true
                        ]
                    ]
                ]
            ],
            $provider->getMappingConfig()
        );

        // Check that cache was used
        $provider->getMappingConfig();

        // Clear cache
        $provider->clearCache();

        // Check that cache was cleared
        $this->assertEquals(
            [
                'Oro\TestBundle\Entity\TestEntity' => [
                    'alias'  => 'test_entity',
                    'fields' => [
                        'firstname' => [
                            'name' => 'firstname',
                            'type' => 'text',
                            'store' => true
                        ],
                        'qty' => [
                            'name' => 'qty',
                            'type' => 'integer',
                            'store' => true
                        ],
                        'email' => [
                            'name' => 'email',
                            'type' => 'text',
                            'store' => true
                        ]
                    ]
                ]
            ],
            $provider->getMappingConfig()
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider()
    {
        $this->mappingConfigurationLoader = $this->createMock(ConfigurationLoaderInterface::class);
        $this->mappingConfigurationLoader->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        return new WebsiteSearchMappingProvider($this->mappingConfigurationLoader, $this->eventDispatcher);
    }
}
