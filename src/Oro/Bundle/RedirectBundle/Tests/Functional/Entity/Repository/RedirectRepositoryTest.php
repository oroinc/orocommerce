<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @dbIsolation
 */
class RedirectRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadRedirects::class
        ]);
    }

    public function testFindByFromWithoutWebsite()
    {
        $result = $this->getContainer()->get('doctrine')->getRepository(Redirect::class)
            ->findByFrom('/from-1');
        
        $this->assertEquals($this->getReference(LoadRedirects::REDIRECT_1), $result);
    }

    /**
     * @dataProvider findByFromWithoutWebsiteDataProvider
     * @param string $website
     * @param string $fromUrl
     * @param string $redirect
     */
    public function testFindByFromWithWebsite($website, $fromUrl, $redirect)
    {
        /** @var Website $website */
        $website = $this->getReference($website);

        $result = $this->getContainer()->get('doctrine')->getRepository(Redirect::class)
            ->findByFrom($fromUrl, $website);

        $this->assertEquals($this->getReference($redirect), $result);
    }

    /**
     * @return array
     */
    public function findByFromWithoutWebsiteDataProvider()
    {
        return [
            [
                'website' => LoadWebsiteData::WEBSITE1,
                'from_url' => '/from-2',
                'redirect' => LoadRedirects::REDIRECT_2
            ],
            [
                'website' => LoadWebsiteData::WEBSITE2,
                'from_url' => '/from-3',
                'redirect' => LoadRedirects::REDIRECT_3
            ]
        ];
    }
}
