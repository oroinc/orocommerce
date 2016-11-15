<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Entity\Repository;

use Doctrine\ORM\EntityRepository;
use Oro\Bundle\EntityBundle\ORM\InsertFromSelectQueryExecutor;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\Repository\RepositoryHolder;

class RepositoryHolderTest extends \PHPUnit_Framework_TestCase
{
    public function testHolder()
    {
        $repositoryMock = $this->getMockBuilder(EntityRepository::class)->disableOriginalConstructor()->getMock();

        $scopeManager = $this->getMockBuilder(ScopeManager::class)->disableOriginalConstructor()->getMock();
        $insertExecutor = $this->getMockBuilder(InsertFromSelectQueryExecutor::class)
            ->disableOriginalConstructor()->getMock();

        $repositoryMock->method('setScopeManager')->with($scopeManager);
        $repositoryMock->method('setInsertExecutor')->with($insertExecutor);

        $holder = new RepositoryHolder($repositoryMock);
        $holder->setScopeManager($scopeManager);
        $holder->setInsertExecutor($insertExecutor);

        $this->assertSame($repositoryMock, $holder->getRepository());
    }
}
