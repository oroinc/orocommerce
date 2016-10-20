<?php

namespace Oro\Bundle\ScopeBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeAccountData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeAccountGroupData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ScopeRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            LoadScopeAccountData::class,
            LoadScopeAccountGroupData::class
        ]);
    }

    public function testFindByCriteria()
    {
        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeAccountData::SCOPE_PREFIX);

        /** @var Account $account */
        $account = $scope->getAccount();
        $this->assertNotEmpty($account);

        $accountCriteria = new ScopeCriteria(['account' => $account->getId()]);
        $accountScope = $this->getRepository()->findByCriteria($accountCriteria);
        $this->assertCount(1, $accountScope);

        $accountGroupCriteria = new ScopeCriteria(['accountGroup' => 2]);
        $accountGroupScope = $this->getRepository()->findByCriteria($accountGroupCriteria);
        $this->assertCount(0, $accountGroupScope);
    }

    public function testFindOneByCriteria()
    {
        /** @var Scope $scope */
        $scope = $this->getReference(LoadScopeAccountGroupData::SCOPE_PREFIX);

        /** @var AccountGroup $accountGroup */
        $accountGroup = $scope->getAccountGroup();
        $this->assertNotEmpty($accountGroup);

        $groupCriteria = new ScopeCriteria(['accountGroup' => $accountGroup->getId()]);
        $accountGroupScope = $this->getRepository()->findOneByCriteria($groupCriteria);
        $this->assertEquals($scope, $accountGroupScope);

        $criteria = new ScopeCriteria(['account' => 1]);
        $accountScope = $this->getRepository()->findOneByCriteria($criteria);
        $this->assertNull($accountScope);
    }

    /**
     * @return ScopeRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroScopeBundle:Scope');
    }
}
