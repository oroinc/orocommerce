<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;

class LandingPageTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCMSBundle/Tests/Functional/Api/Frontend/DataFixtures/landing_page.yml'
        ]);
    }

    /**
     * @return Localization
     */
    private function getCurrentLocalization()
    {
        /** @var UserLocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_frontend_localization.manager.user_localization');

        return $localizationManager->getCurrentLocalization();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => ['gte' => '<toString(@page1->id)>']]]
        );

        $this->assertResponseContains('cget_landing_page.yml', $response);
    }

    public function testGetListFilterById()
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => '<toString(@page3->id)>']]
        );

        $this->assertResponseContains('cget_landing_page_filter_by_id.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>']
        );

        $this->assertResponseContains('get_landing_page.yml', $response);
    }

    public function testGetForAnotherLocalization()
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains('get_landing_page_es.yml', $response);
    }

    public function testGetForAnotherLocalizationForLandingPageOnlyWithDefaultUrl()
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page2->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'url'  => '/page2_slug_default',
                        'urls' => [
                            [
                                'url'            => '/page2_slug_default',
                                'localizationId' => '<toString(@current_localization->id)>'
                            ],
                            ['url' => '/page2_slug_default', 'localizationId' => '<toString(@en_CA->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'landingpages',
                'id'         => '<toString(@page1->id)>',
                'attributes' => [
                    'title' => 'Updated Landing Page Title'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'landingpages',
                'attributes' => [
                    'title' => 'New Landing Page'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'landingpages'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => '<toString(@page1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
