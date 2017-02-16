<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
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

    public function testFindByUrlSuccessful()
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_2);

        $fromUrl = $redirect->getFrom();
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        /** @var Scope $scope */
        $scope = $redirect->getScopes()->first();
        $scopeCriteria = $scopeManager->getCriteriaByScope($scope, 'web_content');

        $result = $this->getContainer()
            ->get('oro_redirect.repository.redirect')
            ->findByUrl($fromUrl, $scopeCriteria);

        $this->assertEquals($redirect->getId(), $result->getId());
    }

    public function testFindByUrlNoMatchedUrl()
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_2);

        $fromUrl = $redirect->getFrom();
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        /** @var Scope $scope */
        $scope = $redirect->getScopes()->first();
        $scopeCriteria = $scopeManager->getCriteriaByScope($scope, 'web_content');

        $result = $this->getContainer()
            ->get('oro_redirect.repository.redirect')
            ->findByUrl($fromUrl . '-unknown', $scopeCriteria);

        $this->assertNull($result);
    }

    public function testFindByUrlNotMatchingCriteria()
    {
        /** @var Redirect $redirect1 */
        $redirect1 = $this->getReference(LoadRedirects::REDIRECT_2);
        /** @var Redirect $redirect2 */
        $redirect2 = $this->getReference(LoadRedirects::REDIRECT_3);

        $fromUrl = $redirect1->getFrom();
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        /** @var Scope $scope */
        $scope = $redirect2->getScopes()->first();
        $scopeCriteria = $scopeManager->getCriteriaByScope($scope, 'web_content');

        $result = $this->getContainer()
            ->get('oro_redirect.repository.redirect')
            ->findByUrl($fromUrl, $scopeCriteria);

        $this->assertNull($result);
    }

    public function testFindByUrlEmptyScopes()
    {
        /** @var Redirect $redirect1 */
        $redirect1 = $this->getReference(LoadRedirects::REDIRECT_1);
        /** @var Redirect $redirect2 */
        $redirect2 = $this->getReference(LoadRedirects::REDIRECT_2);

        $fromUrl = $redirect1->getFrom();
        /** @var ScopeManager $scopeManager */
        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        /** @var Scope $scope */
        $scope = $redirect2->getScopes()->first();
        $scopeCriteria = $scopeManager->getCriteriaByScope($scope, 'web_content');

        $result = $this->getContainer()
            ->get('oro_redirect.repository.redirect')
            ->findByUrl($fromUrl, $scopeCriteria);

        $this->assertEquals($redirect1->getId(), $result->getId());
    }
}
