<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\DependencyInjection;

use Oro\Bundle\TestFrameworkBundle\Test\DependencyInjection\ExtensionTestCase;
use Oro\Bundle\WebsiteSearchBundle\DependencyInjection\OroWebsiteSearchExtension;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixture\Bundle\FirstEngineBundle\FirstEngineBundle;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixture\Bundle\SecondEngineBundle\SecondEngineBundle;
use Oro\Component\Config\CumulativeResourceManager;

class OroWebsiteSearchExtensionTest extends ExtensionTestCase
{
    /** @var OroWebsiteSearchExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new OroWebsiteSearchExtension();

        $bundle1 = new FirstEngineBundle();
        $bundle2 = new SecondEngineBundle();
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles([
                $bundle1->getName() => get_class($bundle1),
                $bundle2->getName() => get_class($bundle2)
            ]);
    }

    public function testLoad()
    {
        $this->loadExtension($this->extension);

        $expectedParameters = [
            'oro_website_search.engine'
        ];
        $this->assertParametersLoaded($expectedParameters);

        $expectedDefinitions = [];
        $this->assertDefinitionsLoaded($expectedDefinitions);
    }

    public function testGetAlias()
    {
        $alias = $this->extension->getAlias();
        $this->assertEquals('oro_website_search', $alias);
    }

    public function testOrmSearchEngineLoad()
    {
        $this->loadExtension(new OroWebsiteSearchExtension(), ['oro_website_search' => ['engine' => 'orm']]);
        $this->assertDefinitionsLoaded([
            'test_orm_service',
        ]);

        $this->assertNotExpectedDefinitionsAreNotLoaded();
    }

    protected function assertNotExpectedDefinitionsAreNotLoaded()
    {
        $notExpectedDefinitions = [
            'test_engine_service',
            'test_engine_first_bundle_service',
            'test_engine_second_bundle_service'
        ];

        foreach ($notExpectedDefinitions as $servicesId) {
            $this->assertArrayNotHasKey(
                $servicesId,
                $this->actualDefinitions,
                sprintf('Definition for "%s" service shouldn\'t be loaded', $servicesId)
            );
        }
    }

    public function testOtherSearchEngineLoad()
    {
        $this->loadExtension(new OroWebsiteSearchExtension(), ['oro_website_search' => ['engine' => 'other_engine']]);
        $this->assertDefinitionsLoaded([
            'test_engine_service',
            'test_engine_first_bundle_service',
            'test_engine_second_bundle_service'
        ]);
    }
}
