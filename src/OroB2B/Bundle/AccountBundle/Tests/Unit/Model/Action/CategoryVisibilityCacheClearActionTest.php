<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Model\Action;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\WorkflowBundle\Model\ContextAccessor;
use Oro\Bundle\WorkflowBundle\Model\ProcessData;

use OroB2B\Bundle\AccountBundle\Model\Action\CategoryVisibilityCacheClearAction;
use OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage;

class CategoryVisibilityCacheClearActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CategoryVisibilityStorage|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $categoryVisibilityStorage;

    /**
     * @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $contextAccessor;

    /**
     * @var CategoryVisibilityCacheClearAction
     */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Bundle\WorkflowBundle\Model\ContextAccessor');
        $this->action    = new CategoryVisibilityCacheClearAction($this->contextAccessor);
        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->categoryVisibilityStorage = $this
            ->getMockBuilder('OroB2B\Bundle\AccountBundle\Visibility\Storage\CategoryVisibilityStorage')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action->setDispatcher($dispatcher);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage CategoryVisibilityStorage is not provided
     */
    public function testInitializeFailed()
    {
        $this->action->initialize([]);
    }

    public function testOptionHash()
    {
        $category = $this->getMock('OroB2B\Bundle\CatalogBundle\Entity\Category');
        $context = new ProcessData(['data' => $category]);
        $this->categoryVisibilityStorage->expects($this->once())
            ->method('flush');

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, '$data')
            ->willReturn($category);

        $this->action->setCategoryVisibilityStorage($this->categoryVisibilityStorage);
        $this->action->initialize(['entity' => '$data']);
        $this->action->execute($context);
    }

    public function testOptionIndex()
    {
        $category = $this->getMock('OroB2B\Bundle\CatalogBundle\Entity\Category');
        $context = new ProcessData(['data' => $category]);
        $this->categoryVisibilityStorage->expects($this->once())
            ->method('flush');

        $this->contextAccessor->expects($this->once())
            ->method('getValue')
            ->with($context, '$data')
            ->willReturn($category);

        $this->action->setCategoryVisibilityStorage($this->categoryVisibilityStorage);
        $this->action->initialize(['$data']);
        $this->action->execute($context);
    }

    public function testExecuteAction()
    {
        $this->assertExecution($this->getMock('OroB2B\Bundle\CatalogBundle\Entity\Category'), 'flush');
    }

    public function testExecuteActionCategoryVisibility()
    {
        $this->assertExecution(
            $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\CategoryVisibility'),
            'clear'
        );
    }

    public function testExecuteActionAccountGroupCategoryVisibility()
    {
        $accountGroup = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountGroup');
        $accountGroupCategoryVisibility = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountGroupCategoryVisibility');
        $accountGroupCategoryVisibility->expects($this->once())
            ->method('getAccountGroup')
            ->willReturn($accountGroup);
        $this->assertExecution($accountGroupCategoryVisibility, 'clearForAccountGroup', [$accountGroup]);
    }

    public function testExecuteActionAccountCategoryVisibility()
    {
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $accountCategoryVisibility = $this
            ->getMock('OroB2B\Bundle\AccountBundle\Entity\Visibility\AccountCategoryVisibility');
        $accountCategoryVisibility->expects($this->once())
            ->method('getAccount')
            ->willReturn($account);
        $this->assertExecution($accountCategoryVisibility, 'clearForAccount', [$account]);
    }

    public function testExecuteActionAccount()
    {
        $account = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\Account');
        $this->assertExecution($account, 'clearForAccount', [$account]);
    }

    /**
     * @param mixed $data
     * @param string $method
     * @param array $arguments
     */
    protected function assertExecution($data, $method, array $arguments = [])
    {
        $context = new ProcessData(['data' => $data]);

        $mocker = $this->categoryVisibilityStorage->expects($this->once())
            ->method($method);

        if (!empty($arguments)) {
            call_user_func_array([$mocker, 'with'], $arguments);
        }

        $this->contextAccessor->expects($this->never())
            ->method('getValue');

        $this->action->setCategoryVisibilityStorage($this->categoryVisibilityStorage);
        $this->action->initialize([]);
        $this->action->execute($context);
    }
}
