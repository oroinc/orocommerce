<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;

use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;

class StartCheckoutTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var WebsiteManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $websiteManager;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;


    /**
     * @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $router;

    /** @var  StartCheckout */
    protected $action;

    public function setUp()
    {
        $this->registry = $this->getMockWithoutConstructor('Symfony\Bridge\Doctrine\ManagerRegistry');
        $this->websiteManager = $this->getMockWithoutConstructor('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager');
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->workflowManager = $this->getMockWithoutConstructor('Oro\Bundle\WorkflowBundle\Model\WorkflowManager');
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->action = new StartCheckout(
            new ContextAccessor(),
            $this->registry,
            $this->websiteManager,
            $this->tokenStorage,
            new PropertyAccessor(),
            $this->workflowManager,
            $this->router
        );
    }

    public function testInitialize()
    {
        $options = [StartCheckout::SOURCE => 'source', StartCheckout::SOURCE_DATA => new \stdClass()];
        $this->assertEquals($this->action, $this->action->initialize($options));
    }

    /**
     * @expectedException \Oro\Bundle\ActionBundle\Exception\InvalidParameterException
     */
    public function testException()
    {
        $this->action->initialize([]);
    }

    /**
     * @return array
     */
    public function testExecuteActionDataProvider()
    {
        return [
            '1' => [
                'context' => [
                    'source' => 'shoppingList',
                    'sourceData' => new ShoppingList(),
                    'data' => [
                        'poNumber' => 123
                    ],
                    'settings' => [
                        'allow_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ]
                ]
            ]
        ];
    }

    /**
     * @param $className
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMockWithoutConstructor($className)
    {
        return $this->getMockBuilder($className)->disableOriginalConstructor()->getMock();
    }
}
