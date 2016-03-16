<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
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

    /**
     * @var  StartCheckout
     */
    protected $action;

    /**
     * @var  PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $propertyAccessor;

    public function setUp()
    {
        $this->registry = $this->getMockWithoutConstructor('Symfony\Bridge\Doctrine\ManagerRegistry');
        $this->websiteManager = $this->getMockWithoutConstructor('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager');
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $this->workflowManager = $this->getMockWithoutConstructor('Oro\Bundle\WorkflowBundle\Model\WorkflowManager');
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');
        $this->propertyAccessor = $this->getMockWithoutConstructor('Symfony\Component\PropertyAccess\PropertyAccessor');
        $this->action = new StartCheckout(
            new ContextAccessor(),
            $this->registry,
            $this->websiteManager,
            $this->tokenStorage,
            $this->propertyAccessor,
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
     * @dataProvider executeActionDataProvider
     * @param array $options
     * @param CheckoutSource|null $checkoutSource
     * @throws \Oro\Bundle\ActionBundle\Exception\InvalidParameterException
     */
    public function testExecute(array $options, CheckoutSource $checkoutSource = null)
    {
        $entity = new ShoppingList();
        $context = new ActionData(['data' => $entity]);

        $this->action->initialize($options);

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $eventDispatcher $eventDispatcher */
        $eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');
        $this->action->setDispatcher($eventDispatcher);


        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $checkoutRepository */
        $checkoutRepository = $this->getMockWithoutConstructor('Doctrine\ORM\EntityRepository');
        $checkoutSourceRepository = clone $checkoutRepository;

        $checkoutSourceRepository->expects($this->once())
            ->method('findOneBy')
            ->with([$options['source'] => $options['sourceData']])
            ->willReturn($checkoutSource);

        $checkoutEm = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $checkoutSourceEm = clone $checkoutEm;

        $checkoutSourceEm->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BCheckoutBundle:CheckoutSource')
            ->willReturn($checkoutSourceRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2BCheckoutBundle:CheckoutSource', $checkoutSourceEm],
                        ['OroB2BCheckoutBundle:Checkout', $checkoutEm]
                    ]
                )
            );
        if (!$checkoutSource) {
            $checkoutSource = new CheckoutSource();
            $account = new Account();
            $account->setOwner(new User());
            $account->setOrganization(new Organization());
            $user = new AccountUser();
            $user->setAccount($account);
            $checkout = new Checkout();
            $checkout->setSource($checkoutSource);
            $checkout->setAccountUser($user);
            $checkout->setWebsite($this->websiteManager->getCurrentWebsite());
            $account = $user->getAccount();
            $checkout->setAccount($account);
            $checkout->setOwner($account->getOwner());
            $checkout->setOrganization($account->getOrganization());
            /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
            $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
            $token->expects($this->once())->method('getUser')->willReturn($user);
            $this->tokenStorage->expects($this->once())->method('getToken')->willReturn($token);
            $this->propertyAccessor
                ->expects($this->once())
                ->method('setValue')
                ->with($checkoutSource, $options['source'], $options['sourceData']);
            $checkoutEm->expects($this->once())->method('persist')->with($checkout);
            $checkoutEm->expects($this->once())->method('flush');
            $this->workflowManager
                ->expects($this->once())
                ->method('startWorkflow')
                ->with(StartCheckout::WORKFLOW_NAME, $checkout);
        } else {
            $checkout = new Checkout();
            $checkoutRepository
                ->expects($this->once())
                ->method('findOneBy')
                ->with(['source' => $checkoutSource])
                ->willReturn($checkout);
            $checkoutEm
                ->expects($this->once())
                ->method('getRepository')
                ->willReturn($checkoutRepository);
        }
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('orob2b_checkout_frontend_checkout', ['id' => $checkout->getId()]);

        $this->action->execute($context);
    }

    /**
     * /**
     * @return array
     */
    public function executeActionDataProvider()
    {
        return [
            'without_checkout_source' => [
                'options' => [
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
                ],
                'checkoutSource' => null
            ],
            'with_checkout_source' => [
                'options' => [
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
                ],
                'checkoutSource' => new CheckoutSource()
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
