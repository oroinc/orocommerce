<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader;

use Oro\Component\Config\CumulativeResourceManager;
use Oro\Bundle\WebsiteSearchBundle\Loader\MappingConfigurationLoader;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader\Fixture\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader\Fixture\Bundle\TestBundle2\TestBundle2;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Loader\Fixture\Bundle\TestBundle3\TestBundle3;

class MappingConfigurationLoaderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var MappingConfigurationLoader
     */
    protected $loader;

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
                $bundle3->getName() => get_class($bundle3),
            ]);

        $this->loader = new MappingConfigurationLoader();
    }

    protected function tearDown()
    {
        unset($this->loader);
        CumulativeResourceManager::getInstance()->clear();
    }

    public function testGetResources()
    {
        $resources = $this->loader->getResources();
        $resourcesPaths = [
            $resources[0]->path,
            $resources[1]->path,
            $resources[2]->path,
        ];

        $expectedResources = [
            $this->getBundleConfigResourcePath('TestBundle2', 'website_search.yml'),
            $this->getBundleConfigResourcePath('TestBundle1', 'website_search.yml'),
            $this->getBundleConfigResourcePath('TestBundle3', 'website_search.yml'),
        ];

        $this->assertEquals($expectedResources, $resourcesPaths);
    }

    public function testGetConfiguration()
    {
        $expectedConfiguration = [
            'Oro\Bundle\TestBundle2\Entity\Page' => [
                'alias' => 'page_WEBSITE_ID',
                'fields' => [
                    [
                        'name' => 'title_LOCALIZATION_ID',
                        'type' => 'text',
                    ],
                    [
                        'name' => 'custom_field',
                        'type' => 'text',
                    ],
                ],
            ],
            'Oro\Bundle\TestBundle3\Entity\Product' => [
                'alias' => 'product_WEBSITE_ID',
                'fields' => [
                    [
                        'name' => 'title_LOCALIZATION_ID',
                        'type' => 'text',
                    ],
                    [
                        'name' => 'price',
                        'type' => 'decimal',
                    ],
                ],
            ],
        ];

        $this->assertEquals($expectedConfiguration, $this->loader->getConfiguration());
    }

    /**
     * @param string $bundleName
     * @param string $resourceFileName
     * @return string
     */
    protected function getBundleConfigResourcePath($bundleName, $resourceFileName)
    {
        return implode(DIRECTORY_SEPARATOR, [
            __DIR__,
            'Fixture',
            'Bundle',
            $bundleName,
            'Resources',
            'config',
            'oro',
            $resourceFileName,
        ]);
    }
}
