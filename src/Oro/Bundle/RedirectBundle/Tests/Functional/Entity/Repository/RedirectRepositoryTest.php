<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RedirectRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadRedirects::class
        ]);
    }

    /**
     * @dataProvider findByFromDataProvider
     * @param string $redirect
     */
    public function testFindByFrom($redirect)
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference($redirect);

        $fromUrl = $redirect->getFrom();
        $scope = $redirect->getScopes()->first();

        $result = $this->getContainer()->get('doctrine')->getRepository(Redirect::class)
            ->findByFrom($fromUrl, $scope);

        $this->assertEquals($redirect, $result);
    }

    /**
     * @return array
     */
    public function findByFromDataProvider()
    {
        return [
            [
                'redirect' => LoadRedirects::REDIRECT_1
            ],
            [
                'redirect' => LoadRedirects::REDIRECT_2
            ],
            [
                'redirect' => LoadRedirects::REDIRECT_3
            ]
        ];
    }
}
