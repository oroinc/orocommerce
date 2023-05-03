<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;

class FeatureAwareRestJsonApiTestCase extends RestJsonApiTestCase
{
    private static bool $featureState;
    private static bool $isFeatureStateChanged = false;

    protected function setUp(): void
    {
        parent::setUp();

        if (!self::$isFeatureStateChanged) {
            $configManager = $this->getContainer()->get('oro_config.global');
            self::$featureState = $configManager->get('oro_website_search.enable_global_search_history_feature');
            if (!self::$featureState) {
                $configManager->set('oro_website_search.enable_global_search_history_feature', true);
                $configManager->flush();
                self::$isFeatureStateChanged = true;
            }
        }
    }

    public static function tearDownAfterClass(): void
    {
        if (self::$isFeatureStateChanged) {
            $configManager = self::getContainer()->get('oro_config.global');
            $configManager->set('oro_website_search.enable_global_search_history_feature', self::$featureState);
            $configManager->flush();

            self::$isFeatureStateChanged = false;
        }

        parent::tearDownAfterClass();
    }
}
