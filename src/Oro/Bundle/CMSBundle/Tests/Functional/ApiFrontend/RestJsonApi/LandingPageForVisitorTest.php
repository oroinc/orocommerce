<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LandingPageForVisitorTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            '@OroCMSBundle/Tests/Functional/ApiFrontend/DataFixtures/landing_page.yml'
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => ['gte' => '<toString(@page1->id)>']]]
        );

        $this->assertResponseContains('cget_landing_page.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>']
        );

        $this->assertResponseContains('get_landing_page.yml', $response);
    }
}
