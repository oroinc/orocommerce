<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class AjaxLocalizationControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadAccountUserData::AUTH_USER, LoadAccountUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadLocalizationData::class,
        ]);
    }

    /**
     * @dataProvider setCurrentLocalizationProvider
     */
    public function testSetCurrentLocalizationAction($code, $expectedResult)
    {
        $localization = $this->getLocalizationByCode($code);

        $params = ['localization' => $localization->getId()];
        $this->client->request('POST', $this->getUrl('oro_frontend_localization_frontend_set_current_localization'), $params);
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertSame($expectedResult, $data);

        /* @var $localizationManager UserLocalizationManager */
        $localizationManager = $this->getContainer()->get('oro_frontend_localization.manager.user_localization');

        $this->assertEquals($localization->getId(), $localizationManager->getCurrentLocalization()->getId());
    }

    /**
     * @return array
     */
    public function setCurrentLocalizationProvider()
    {
        return [
            [
                'localization' => 'en',
                'expectedResult' => ['success' => true] ,
            ],
            [
                'localization' => 'en_US',
                'expectedResult' => ['success' => true] ,
            ],
        ];
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalizationByCode($code)
    {
        $registry = $this->getContainer()->get('doctrine');

        return $registry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class)
            ->findOneBy(['languageCode' => $code]);
    }
}
