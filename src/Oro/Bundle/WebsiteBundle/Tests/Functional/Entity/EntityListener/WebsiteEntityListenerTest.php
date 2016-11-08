<?php

namespace Oro\Bundle\WebsiteBundle\Tests\Functional\Entity\EntityListener;

use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;

/**
 * @dbIsolation
 */
class WebsiteEntityListenerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testPrePersist()
    {
        $em = $this->getClient()->getContainer()->get('doctrine')->getManager();
        $website = new Website();
        $website->setName('test');
        $em->persist($website);
        $em->flush();

        $criteria = new ScopeCriteria(['website' => $website]);
        /** @var ScopeRepository $repository */
        $repository = $em->getRepository(Scope::class);

        // expect that new scope created
        $scope = $repository->findOneByCriteria($criteria);
        $this->assertNotNull($scope);
    }
}
