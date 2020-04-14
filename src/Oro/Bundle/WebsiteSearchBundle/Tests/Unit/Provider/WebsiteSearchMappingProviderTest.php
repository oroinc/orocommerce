<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class WebsiteSearchMappingProviderTest extends \PHPUnit\Framework\TestCase
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

    public function testGetEntityAliasesForUnknownEntity()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'The search alias for the entity "Oro\TestBundle\Entity\UnknownEntity" not found.'
        );

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
        $this->assertEquals($this->testMapping, $this->getProvider()->getMappingConfig());
    }

    /**
     * @return WebsiteSearchMappingProvider
     */
    protected function getProvider()
    {
        /** @var MappingConfigurationProvider|\PHPUnit\Framework\MockObject\MockObject $mappingConfigurationProvider */
        $mappingConfigurationProvider = $this->createMock(MappingConfigurationProvider::class);
        $mappingConfigurationProvider->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        return new WebsiteSearchMappingProvider($mappingConfigurationProvider);
    }
}
