<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadBuyerCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class CategoryForBuyerTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadBuyerCustomerUserData::class,
            '@OroCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/category.yml'
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
            ['entity' => 'mastercatalogcategories']
        );

        $this->assertResponseContains('cget_category.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']
        );

        $this->assertResponseContains('get_category.yml', $response);
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'id'         => '<toString(@category1->id)>',
                'attributes' => [
                    'title' => 'Updated Category'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'attributes' => [
                    'title' => 'New Category'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'mastercatalogcategories'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'mastercatalogcategories'],
            ['filter' => ['id' => '<toString(@category1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
