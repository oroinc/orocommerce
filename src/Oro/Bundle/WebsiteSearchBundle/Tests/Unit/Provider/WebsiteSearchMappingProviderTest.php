<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Doctrine\Common\Cache\Cache;
use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\WebsiteSearchMappingEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebsiteSearchMappingProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var Cache|MockObject */
    private $cache;

    /** @var EventDispatcherInterface|MockObject */
    private $eventDispatcher;

    /** @var MappingConfigurationProvider|MockObject */
    private $mappingConfigurationProvider;

    /** @var WebsiteSearchMappingProvider  */
    private $mappingProvider;

    /** @var array */
    private $testMapping = [
        'Oro\TestBundle\Entity\TestEntity' => [
            'alias'  => 'test_entity',
            'fields' => [
                [
                    'name'  => 'firstname',
                    'type'  => 'text',
                    'store' => true,
                ],
                [
                    'name'  => 'qty',
                    'type'  => 'integer',
                    'store' => true,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->cache = $this->createMock(Cache::class);
        $this->mappingConfigurationProvider = $this->createMock(MappingConfigurationProvider::class);
        $this->mappingConfigurationProvider->expects(self::any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        $this->mappingProvider = new WebsiteSearchMappingProvider(
            $this->mappingConfigurationProvider,
            $this->eventDispatcher,
            $this->cache
        );
    }

    public function testGetEntitiesListAliases()
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->mappingProvider->getEntitiesListAliases()
        );
    }

    public function testGetEntityAliases()
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->mappingProvider->getEntityAliases(['Oro\TestBundle\Entity\TestEntity'])
        );
    }

    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.'
        );

        $this->mappingProvider->getEntityAliases(
            ['Oro\TestBundle\Entity\TestEntity', 'Oro\TestBundle\Entity\UnknownEntity']
        );
    }

    public function testGetEntityAliasesForEmptyClassNames()
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity' => 'test_entity'],
            $this->mappingProvider->getEntityAliases()
        );
    }

    public function testGetEntityAlias()
    {
        self::assertEquals(
            'test_entity',
            $this->mappingProvider->getEntityAlias('Oro\TestBundle\Entity\TestEntity')
        );
    }

    public function testGetEntityAliasForUnknownEntity()
    {
        self::assertNull(
            $this->mappingProvider->getEntityAlias('Oro\TestBundle\Entity\UnknownEntity')
        );
    }

    public function testGetEntityClasses()
    {
        self::assertEquals(
            ['Oro\TestBundle\Entity\TestEntity'],
            $this->mappingProvider->getEntityClasses()
        );
    }

    public function testIsClassSupported()
    {
        self::assertTrue($this->mappingProvider->isClassSupported('Oro\TestBundle\Entity\TestEntity'));
        self::assertFalse($this->mappingProvider->isClassSupported('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testHasFieldsMapping()
    {
        self::assertTrue($this->mappingProvider->hasFieldsMapping('Oro\TestBundle\Entity\TestEntity'));
        self::assertFalse($this->mappingProvider->hasFieldsMapping('Oro\TestBundle\Entity\BadEntity'));
    }

    public function testGetEntityMapParameter()
    {
        self::assertEquals(
            'test_entity',
            $this->mappingProvider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'alias')
        );
        self::assertFalse(
            $this->mappingProvider->getEntityMapParameter('Oro\TestBundle\Entity\TestEntity', 'badParameter', false)
        );
    }

    public function testGetEntityClass()
    {
        self::assertEquals(
            'Oro\TestBundle\Entity\TestEntity',
            $this->mappingProvider->getEntityClass('test_entity')
        );
    }

    public function testGetEntityClassForUnknownAlias()
    {
        self::assertNull($this->mappingProvider->getEntityClass('unknown_entity'));
    }

    public function testGetMappingConfig()
    {
        $expectedMapping = [
            'Oro\TestBundle\Entity\TestEntity' => [
                'alias'  => 'test_entity',
                'fields' => [
                    'firstname' => [
                        'name'            => 'firstname',
                        'type'            => 'text',
                        'store'           => true,
                        'fulltext'        => true,
                        'organization_id' => null,
                    ],
                    'qty'       => [
                        'name'            => 'qty',
                        'type'            => 'integer',
                        'store'           => true,
                        'fulltext'        => false,
                        'organization_id' => null,
                    ],
                    'added1' => [
                        'name'            => 'added1',
                        'type'            => 'text',
                        'fulltext'        => true,
                        'organization_id' => null,
                    ],
                    'added2'       => [
                        'name'            => 'added2',
                        'type'            => 'integer',
                        'fulltext'        => false,
                        'organization_id' => null,
                    ],
                ],
            ],
        ];

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (WebsiteSearchMappingEvent $event) {
                    $event->setConfiguration(
                        [
                            'Oro\TestBundle\Entity\TestEntity' => [
                                'fields' => [
                                    'added1' => [
                                        'name'            => 'added1',
                                        'type'            => 'text'
                                    ],
                                    'added2'       => [
                                        'name'            => 'added2',
                                        'type'            => 'integer'
                                    ],
                                ],
                            ]
                        ]
                    );
                }
            );

        $time = time();
        $this->mappingConfigurationProvider->expects(self::once())
            ->method('getCacheTimestamp')
            ->willReturn($time);
        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $this->cache->expects(self::once())
            ->method('save')
            ->with(
                'oro_website_search.mapping_config',
                [$time, $expectedMapping]
            );

        $mappingConfig = $this->mappingProvider->getMappingConfig();
        self::assertEquals($expectedMapping, $mappingConfig);
        self::assertEquals($mappingConfig, $this->mappingProvider->getMappingConfig());
    }

    public function testClearCache()
    {
        $expectedMapping = [
            'Oro\TestBundle\Entity\TestEntity' => [
                'alias'  => 'test_entity',
                'fields' => [
                    'firstname' => [
                        'name'            => 'firstname',
                        'type'            => 'text',
                        'store'           => true,
                        'fulltext'        => true,
                        'organization_id' => null,
                    ],
                    'qty'       => [
                        'name'            => 'qty',
                        'type'            => 'integer',
                        'store'           => true,
                        'fulltext'        => false,
                        'organization_id' => null,
                    ]
                ],
            ],
        ];

        $this->cache->expects(self::once())
            ->method('delete')
            ->with('oro_website_search.mapping_config');
        $this->cache->expects(self::once())
            ->method('fetch')
            ->willReturn(false);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch');
        $this->mappingConfigurationProvider->expects(self::never())
            ->method('clearCache');
        $this->mappingConfigurationProvider->expects(self::never())
            ->method('warmUpCache');

        $this->mappingProvider->clearCache();

        static::assertEquals($expectedMapping, $this->mappingProvider->getMappingConfig());
    }

    public function testWarmUpCache()
    {
        $this->cache->expects(self::once())
            ->method('delete')
            ->with('oro_website_search.mapping_config');
        $this->cache->expects(self::once())
            ->method('fetch')
            ->with('oro_website_search.mapping_config')
            ->willReturn(false);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->willReturnCallback(
                static function (WebsiteSearchMappingEvent $event) {
                    $event->setConfiguration(
                        [
                            'Oro\TestBundle\Entity\TestEntity' => [
                                'fields' => [
                                    'added1' => [
                                        'name'            => 'added1',
                                        'type'            => 'text'
                                    ]
                                ],
                            ]
                        ]
                    );
                }
            );

        $this->mappingProvider->warmUpCache();

        $expectedMapping = [
            'Oro\TestBundle\Entity\TestEntity' => [
                'alias'  => 'test_entity',
                'fields' => [
                    'firstname' => [
                        'name'            => 'firstname',
                        'type'            => 'text',
                        'store'           => true,
                        'fulltext'        => true,
                        'organization_id' => null,
                    ],
                    'qty'       => [
                        'name'            => 'qty',
                        'type'            => 'integer',
                        'store'           => true,
                        'fulltext'        => false,
                        'organization_id' => null,
                    ],
                    'added1' => [
                        'name'            => 'added1',
                        'type'            => 'text',
                        'fulltext'        => true,
                        'organization_id' => null,
                    ]
                ],
            ],
        ];

        static::assertEquals($expectedMapping, $this->mappingProvider->getMappingConfig());
    }
}
