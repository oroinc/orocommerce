<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Unit\Provider\AbstractSearchMappingProviderTest;
use Oro\Bundle\WebsiteSearchBundle\Loader\ConfigurationLoaderInterface;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

class WebsiteSearchMappingProviderTest extends AbstractSearchMappingProviderTest
{
    /** @var WebsiteSearchMappingProvider */
    protected $provider;

    /** @var ConfigurationLoaderInterface|\PHPUnit_Framework_MockObject_MockObject $mappingConfigurationLoader */
    protected $mappingConfigurationLoader;

    protected function tearDown()
    {
        parent::tearDown();
        unset($this->mappingConfigurationLoader);
    }

    public function testGetMappingConfig()
    {
        $this->assertEquals($this->testMapping, $this->provider->getMappingConfig());
        $this->provider->getMappingConfig();
    }

    /**
     * {@inheritdoc}
     */
    protected function getProvider()
    {
        $this->mappingConfigurationLoader = $this->getMock(ConfigurationLoaderInterface::class);
        $this->mappingConfigurationLoader
            ->expects($this->any())
            ->method('getConfiguration')
            ->willReturn($this->testMapping);

        return new WebsiteSearchMappingProvider($this->mappingConfigurationLoader);
    }

    public function testMapSelectedData()
    {
        $query = new Query();
        $query->select('title');
        $query->addSelect('codes');

        $item = [
            'title' => 'Test item title',
            'codes' => [
                'code1',
                'code2',
            ],
            'description' => 'I don\'t want to select it',
        ];

        $expectedResult = [
            'title' => 'Test item title',
            'codes' => 'code1',
        ];

        $this->assertEquals($expectedResult, $this->provider->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataEmptySelect()
    {
        $query = new Query();
        $item = [
            'title' => 'Test item title',
        ];

        $this->assertNull($this->provider->mapSelectedData($query, $item));
    }

    public function testMapSelectedDataEmptyItem()
    {
        $query = new Query();
        $query->select('title');
        $query->addSelect('codes');

        $item = [];

        $expectedResult = [
            'title' => '',
            'codes' => '',
        ];

        $this->assertEquals($expectedResult, $this->provider->mapSelectedData($query, $item));
    }
}
