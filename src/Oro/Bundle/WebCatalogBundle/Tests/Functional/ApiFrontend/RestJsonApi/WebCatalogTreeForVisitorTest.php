<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class WebCatalogTreeForVisitorTest extends WebCatalogTreeTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroWebCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/content_node.yml'
        ]);
        $this->switchToWebCatalog();

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
            ['entity' => 'webcatalogtree']
        );
        $this->assertResponseContains('cget_content_node.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']
        );
        $this->assertResponseContains('get_content_node.yml', $response);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'data' => [
                    'type' => 'webcatalogtree',
                    'id' => '<toString(@catalog1_node11->id)>',
                    'attributes' => [
                        'title' => 'Updated Node'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'webcatalogtree'],
            [
                'data' => [
                    'type' => 'webcatalogtree',
                    'attributes' => [
                        'title' => 'New Node'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['id' => '<toString(@catalog1_node11->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
