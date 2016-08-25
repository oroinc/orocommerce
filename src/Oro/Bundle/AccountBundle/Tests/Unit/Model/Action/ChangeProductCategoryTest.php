<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\ProcessData;
use Oro\Component\Action\Model\ContextAccessor;
use Oro\Bundle\AccountBundle\Visibility\Cache\CacheBuilderInterface;
use Oro\Bundle\AccountBundle\Model\Action\ChangeProductCategory;
use Oro\Bundle\ProductBundle\Entity\Product;

class ChangeProductCategoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var CacheBuilderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cacheBuilder;

    /**
     * @var ChangeProductCategory
     */
    protected $action;

    protected function setUp()
    {
        $this->registry = $this->getMock('Symfony\Bridge\Doctrine\RegistryInterface');
        $this->cacheBuilder = $this->getMock(
            'Oro\Bundle\AccountBundle\Visibility\Cache\ProductCaseCacheBuilderInterface'
        );
        /** @var EventDispatcherInterface $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $contextAccessor = new ContextAccessor();
        $this->action = new ChangeProductCategory($contextAccessor);
        $this->action->setRegistry($this->registry);
        $this->action->setCacheBuilder($this->cacheBuilder);
        $this->action->setDispatcher($eventDispatcher);
    }

    public function testExecute()
    {
        $entity = new Product();

        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $entityManager->expects($this->any())
            ->method('beginTransaction');
        $entityManager->expects($this->any())
            ->method('commit');

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with('OroAccountBundle:VisibilityResolved\ProductVisibilityResolved')
            ->willReturn($entityManager);

        $this->cacheBuilder->expects($this->once())
            ->method('productCategoryChanged')
            ->with($entity);

        $this->action->initialize([]);
        $this->action->execute(new ProcessData(['data' => $entity]));
    }
}
