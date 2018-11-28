<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebsiteSearchMappingProviderTest extends \PHPUnit_Framework_TestCase
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

    /** @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingConfigurationLoader;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $eventDispatcher;

    protected function setUp()
    {
        parent::setUp();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
    }

    public function testGetEntitiesListAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.
     */
    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->getProvider()->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->getProvider()->getEntityAliases()
        );
    }

    public function testGetEntityAlias()
    {
        $this->assertEquals(
            'test_entity',
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity()
    {
        $this->assertNull(
            $this->getProvider()->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses()
    {
        $this->assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->getProvider()->getEntityClasses()
        );
    }

    public function testIsClassSupported()
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($provider->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testHasFieldsMapping()
    {
        $provider = $this->getProvider();

        $this->assertTrue($provider->hasFieldsMapping('Oro\TestBundle\Entity\TestEntity'));
        $this->assertFalse($provider->hasFieldsMapping('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter()
    {
        $provider = $this->getProvider();

        $this->assertEquals(
            'test_entity',
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        $this->assertFalse(
            $provider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter', false)
        );
    }

    public function testGetEntityClass()
    {
        $this->assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->getProvider()->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias()
    {
        $this->assertNull(
            $this->getProvider()->getEntityClass('unknown_entity')
        );
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
     * @return WebsiteSearchMappingProvider
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
