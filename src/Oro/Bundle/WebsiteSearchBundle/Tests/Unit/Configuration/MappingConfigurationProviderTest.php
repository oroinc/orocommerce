<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Configuration;

use Oro\Bundle\WebsiteSearchBundle\Configuration\MappingConfigurationProvider;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle1\TestBundle1;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle2\TestBundle2;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Provider\Fixture\Bundle\TestBundle3\TestBundle3;
use Oro\Component\Config\CumulativeResourceManager;
use Oro\Component\Testing\TempDirExtension;

class MappingConfigurationProviderTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $cacheFile;

    /** @var MappingConfigurationProvider */
    private $configurationProvider;

    protected function setUp(): void
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

        $this->configurationProvider = new MappingConfigurationProvider(
            $this->cacheFile,
            false
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

        $this->assertEquals($cachedConfig, $this->configurationProvider->getConfiguration());
    }

    public function testGetConfigurationWithoutCache()
    {
        $this->configurationProvider->clearCache();

        $config = $this->configurationProvider->getConfiguration();

        $expectedConfig = [
            'Oro\Bundle\TestBundle2\Entity\Page' => [
                'alias' => 'page_WEBSITE_ID',
                'synonyms_enabled' => false,
                'fields' => [
                    'title_LOCALIZATION_ID' => [
                        'name' => 'title_LOCALIZATION_ID',
                        'type' => 'text',
                        'fulltext' => true,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'test_first_repeating_field' => [
                        'name' => 'test_first_repeating_field',
                        'type' => 'integer',
                        'fulltext' => false,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'test_second_repeating_field' => [
                        'name' => 'test_second_repeating_field',
                        'type' => 'integer',
                        'fulltext' => false,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'custom_field' => [
                        'name' => 'custom_field',
                        'type' => 'text',
                        'fulltext' => true,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ]
                ]
            ],
            'Oro\Bundle\TestBundle3\Entity\Product' => [
                'alias' => 'product_WEBSITE_ID',
                'synonyms_enabled' => false,
                'fields' => [
                    'title_LOCALIZATION_ID' => [
                        'name' => 'title_LOCALIZATION_ID',
                        'type' => 'text',
                        'fulltext' => true,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                    'price' => [
                        'name' => 'price',
                        'type' => 'decimal',
                        'fulltext' => false,
                        'organization_id' => null,
                        'store' => true,
                        'group' => 'main'
                    ],
                ]
            ]
        ];

        $this->assertEquals($expectedConfig, $config);
    }
}
