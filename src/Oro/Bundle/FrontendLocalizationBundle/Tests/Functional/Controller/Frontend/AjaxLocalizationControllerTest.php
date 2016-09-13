<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Controller\Frontend;

use Symfony\Component\HttpFoundation\Request;

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
     *
     * @param string $code
     * @param array $expectedResult
     */
    public function testSetCurrentLocalizationAction($code, array $expectedResult)
    {
        $localization = $this->getLocalizationByCode($code);

        $this->client->request(
            Request::METHOD_POST,
            $this->getUrl('oro_frontend_localization_frontend_set_current_localization'),
            ['localization' => $localization->getId()]
        );
        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertSame($expectedResult, $data);

        $website = $this->client->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        /* @var $localizationManager UserLocalizationManager */
        $localizationManager = $this->getContainer()->get('oro_frontend_localization.manager.user_localization');
        $currentLocalization = $localizationManager->getCurrentLocalization($website);

        $this->assertNotEmpty($localization);
        $this->assertNotEmpty($currentLocalization);
        $this->assertEquals($localization->getId(), $currentLocalization->getId());
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
