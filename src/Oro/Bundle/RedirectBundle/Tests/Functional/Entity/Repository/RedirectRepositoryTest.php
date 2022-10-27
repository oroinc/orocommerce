<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\RedirectBundle\Entity\Redirect;
use Oro\Bundle\RedirectBundle\Entity\Repository\RedirectRepository;
use Oro\Bundle\RedirectBundle\Entity\Slug;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadRedirects;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RedirectRepositoryTest extends WebTestCase
{
    /**
     * @var RedirectRepository
     */
    private $repository;

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadRedirects::class
        ]);

        $this->repository = $this->getContainer()->get('doctrine')->getRepository(Redirect::class);
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

        $result = $this->repository->findByUrl($fromUrl, $scopeCriteria);

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

        $result = $this->repository->findByUrl($fromUrl . '-unknown', $scopeCriteria);

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

        $result = $this->repository->findByUrl($fromUrl, $scopeCriteria);

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

        $result = $this->repository->findByUrl($fromUrl, $scopeCriteria);

        $this->assertEquals($redirect1->getId(), $result->getId());
    }

    public function testFindByPrototypeSuccessful()
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_2);

        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $scopeCriteria = $scopeManager->getCriteriaByScope($redirect->getScopes()->first(), 'web_content');

        $result = $this->repository->findByPrototype($redirect->getFromPrototype(), $scopeCriteria);

        $this->assertEquals($redirect->getId(), $result->getId());
    }

    public function testFindByPrototypeNoMatchedPrototype()
    {
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_2);

        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $scopeCriteria = $scopeManager->getCriteriaByScope($redirect->getScopes()->first(), 'web_content');

        $result = $this->repository->findByPrototype($redirect->getFromPrototype() . '-unknown', $scopeCriteria);

        $this->assertNull($result);
    }

    public function testFindByPrototypeNotMatchingCriteria()
    {
        /** @var Redirect $redirect1 */
        $redirect1 = $this->getReference(LoadRedirects::REDIRECT_2);
        /** @var Redirect $redirect2 */
        $redirect2 = $this->getReference(LoadRedirects::REDIRECT_3);

        $scopeManager = $this->getContainer()->get('oro_scope.scope_manager');
        $scopeCriteria = $scopeManager->getCriteriaByScope($redirect2->getScopes()->first(), 'web_content');

        $result = $this->repository->findByPrototype($redirect1->getFromPrototype(), $scopeCriteria);

        $this->assertNull($result);
    }

    public function testUpdateRedirectsBySlug()
    {
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $this->repository->updateRedirectsBySlug($slug);
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Redirect::class);
        $em->refresh($redirect);
        $this->assertEquals($slug->getUrl(), $redirect->getTo());
    }

    public function testDeleteCyclicRedirects()
    {
        $em = $this->getContainer()->get('doctrine')->getManagerForClass(Redirect::class);
        /** @var Redirect $redirect */
        $redirect = $this->getReference(LoadRedirects::REDIRECT_1);
        /** @var Slug $slug */
        $slug = $this->getReference(LoadSlugsData::SLUG_URL_ANONYMOUS);

        $redirect->setFrom($slug->getUrl());
        $redirect->setTo($slug->getUrl());
        $em->flush();

        $this->repository->deleteCyclicRedirects($slug);

        $this->assertNull($em->getRepository(Redirect::class)->findOneBy(['id' => $redirect->getId()]));
    }
}
