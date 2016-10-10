<?php

namespace Oro\Bundle\ScopeBundle\Tests\Unit\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\ScopeBundle\Entity\Repository\ScopeRepository;
use Oro\Bundle\ScopeBundle\Entity\Scope;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;
use Oro\Bundle\ScopeBundle\Manager\ScopeCriteriaProviderInterface;
use Oro\Bundle\ScopeBundle\Model\ScopeCriteria;

class ScopeManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeManager
     */
    protected $manager;

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var EntityFieldProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFieldProvider;

    public function setUp()
    {
        $this->registry = $this->getMock(ManagerRegistry::class);
        $this->entityFieldProvider = $this->getMockBuilder(EntityFieldProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->manager = new ScopeManager($this->registry, $this->entityFieldProvider);
    }

    public function tearDown()
    {
        unset($this->manager, $this->registry, $this->entityFieldProvider);
    }

    public function testFind()
    {
        $scope = new Scope();
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn(['fieldName' => 1]);
        $scopeCriteria = new ScopeCriteria(['fieldName' => 1, 'fieldName2' => null]);
        $repository = $this->getMockBuilder(ScopeRepository::class)->disableOriginalConstructor()->getMock();
        $repository->method('findOneByCriteria')->with($scopeCriteria)->willReturn($scope);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->entityFieldProvider->method('getRelations')->willReturn([
            ['name' => 'fieldName'],
            ['name' => 'fieldName2']
        ]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->find('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindOrCreate()
    {
        $scope = new Scope();
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaForCurrentScope')->willReturn([]);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->once())->method('persist')->with($scope);
        $em->expects($this->once())->method('flush')->with($scope);

        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope');
        $this->assertEquals($scope, $actualScope);
    }

    public function testFindOrCreateUsingContext()
    {
        $scope = new Scope();
        $context = ['scopeAttribute' => new \stdClass()];
        $provider = $this->getMock(ScopeCriteriaProviderInterface::class);
        $provider->method('getCriteriaByContext')->with($context)->willReturn([]);

        $repository = $this->getMock(ObjectRepository::class);
        $repository->method('findOneBy')->willReturn(null);

        $em = $this->getMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturn($repository);
        $em->expects($this->once())->method('persist')->with($scope);
        $em->expects($this->once())->method('flush')->with($scope);

        $this->registry->method('getManagerForClass')->willReturn($em);

        $this->entityFieldProvider->method('getRelations')->willReturn([]);

        $this->manager->addProvider('testScope', $provider);
        $actualScope = $this->manager->findOrCreate('testScope', $context);
        $this->assertEquals($scope, $actualScope);
    }
}
