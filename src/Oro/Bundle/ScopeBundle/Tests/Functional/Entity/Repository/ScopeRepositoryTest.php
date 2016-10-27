<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ScopeRepositoryTest extends WebTestCase
{
    /**
     * @var Scope
     */
    protected $testScope;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->getRepository()
            ->createQueryBuilder('s')
            ->delete()
            ->getQuery()
            ->execute();

        $this->testScope = new Scope();
        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->persist($this->testScope);
        $em->flush();
    }

    public function testFindByCriteria()
    {
        $criteria = new ScopeCriteria([]);
        $scopes = $this->getRepository()->findByCriteria($criteria);
        $this->assertCount(1, $scopes);
    }

    public function testFindOneByCriteria()
    {
        $criteria = new ScopeCriteria([]);
        $scope = $this->getRepository()->findOneByCriteria($criteria);
        $this->assertNotNull($scope);
    }

    public function testFindScalarByCriteria()
    {
        $criteria = new ScopeCriteria([]);
        $ids = $this->getRepository()->findIdentifiersByCriteria($criteria);
        $this->assertSame([$this->testScope->getId()], $ids);
    }

    /**
     * @return ScopeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroScopeBundle:Scope');
    }
}
