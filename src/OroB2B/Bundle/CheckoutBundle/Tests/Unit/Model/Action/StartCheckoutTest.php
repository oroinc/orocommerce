<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\Model\Action;

use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

use Oro\Component\Action\Action\AbstractAction;
use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Model\Action\StartCheckout;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEvents;

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
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $currencyManager;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $tokenStorage;

    /**
     * @var AbstractAction|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $redirect;

    /**
     * @var  StartCheckout
     */
    protected $action;

    /**
     * @var  PropertyAccessor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $propertyAccessor;

    /**
     * @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $eventDispatcher;

    public function setUp()
    {
        $this->registry = $this->getMockWithoutConstructor('Symfony\Bridge\Doctrine\ManagerRegistry');
        $this->websiteManager = $this->getMockWithoutConstructor('OroB2B\Bundle\WebsiteBundle\Manager\WebsiteManager');
        $this->setUpTokenStorage();
        $this->currencyManager = $this
            ->getMockWithoutConstructor('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager');
        $this->redirect = $this->getMockBuilder('Oro\Component\Action\Action\AbstractAction')
            ->disableOriginalConstructor()
            ->setMethods(['initialize', 'execute'])
            ->getMockForAbstractClass();
        $this->propertyAccessor = $this
            ->getMockWithoutConstructor('Symfony\Component\PropertyAccess\PropertyAccessor');
        $this->eventDispatcher = $this->getMock('Symfony\Component\EventDispatcher\EventDispatcherInterface');

        $this->action = new StartCheckout(
            new ContextAccessor(),
            $this->registry,
            $this->websiteManager,
            $this->currencyManager,
            $this->tokenStorage,
            $this->propertyAccessor,
            $this->redirect,
            $this->eventDispatcher
        );

        $this->action->setDispatcher($this->eventDispatcher);
        $this->action->setCheckoutRoute('orob2b_checkout_frontend_checkout');
    }

    public function testInitialize()
    {
        $options = [StartCheckout::SOURCE_FIELD_KEY => 'source', StartCheckout::SOURCE_ENTITY_KEY => new \stdClass()];
        $this->assertEquals($this->action, $this->action->initialize($options));
    }

    /**
     * @expectedException \Oro\Component\Action\Exception\InvalidParameterException
     */
    public function testException()
    {
        $this->action->initialize([]);
    }

    /**
     * @dataProvider executeActionDataProvider
     * @param array $options
     * @param CheckoutSource|null $checkoutSource
     */
    public function testExecute(array $options, CheckoutSource $checkoutSource = null)
    {
        $checkout = new Checkout();
        $entity = new ShoppingList();

        $this->eventDispatcher
            ->expects($this->any())
            ->method('dispatch')
            ->willReturnCallback(function ($eventName, $event) use ($checkout) {
                if ($eventName === CheckoutEvents::GET_CHECKOUT_ENTITY && $event instanceof CheckoutEntityEvent) {
                    $event->setCheckoutEntity($checkout);
                }
            });

        $context = new ActionData(['data' => $entity]);

        $this->action->initialize($options);

        $checkoutSourceRepository = $this->getMockWithoutConstructor('Doctrine\ORM\EntityRepository');
        $checkoutSourceRepository->expects($this->once())
            ->method('findOneBy')
            ->with([$options[StartCheckout::SOURCE_FIELD_KEY] => $options[StartCheckout::SOURCE_ENTITY_KEY]])
            ->willReturn($checkoutSource);

        $em = $this->getMockBuilder('\Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->with('OroB2BCheckoutBundle:CheckoutSource')
            ->willReturn($checkoutSourceRepository);

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->will($this->returnValue($em));

        if (!$checkoutSource) {
            $propertyAccessor = PropertyAccess::createPropertyAccessor();
            $this->propertyAccessor
                ->expects($this->once())
                ->method('setValue')
                ->willReturnCallback(
                    function ($entity, $key, $value) use ($options, $propertyAccessor) {
                        if ($entity instanceof CheckoutSource) {
                            \PHPUnit_Framework_Assert::assertEquals($key, $options[StartCheckout::SOURCE_FIELD_KEY]);
                            \PHPUnit_Framework_Assert::assertEquals($value, $options[StartCheckout::SOURCE_ENTITY_KEY]);
                        } else {
                            $propertyAccessor->setValue($entity, $key, $value);
                        }
                    }
                );

            $em->expects($this->once())
                ->method('persist')
                ->with($this->isInstanceOf('OroB2B\Bundle\CheckoutBundle\Entity\Checkout'))
                ->willReturnCallback(
                    function (Checkout $entity) {
                        $entity->setWorkflowItem(new WorkflowItem());
                    }
                );
            $em->expects($this->exactly(2))->method('flush');
        } else {
            $checkout->setWorkflowItem(new WorkflowItem());
        }

        $this->redirect
            ->expects($this->once())
            ->method('initialize')
            ->with(
                [
                    'route' => 'orob2b_checkout_frontend_checkout',
                    'route_parameters' => ['id' => $checkout->getId(), 'checkoutType' => $checkout->getCheckoutType()]
                ]
            );
        $this->redirect->expects($this->once())
            ->method('execute')
            ->with($context);

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
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList(),
                    StartCheckout::CHECKOUT_DATA_KEY => [
                        'poNumber' => 123
                    ],
                    StartCheckout::SETTINGS_KEY => [
                        'allow_manual_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ]
                ],
                'checkoutSource' => null
            ],
            'without_checkout_source minimal' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList()
                ],
                'checkoutSource' => null
            ],
            'with_checkout_source' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList(),
                    StartCheckout::CHECKOUT_DATA_KEY => [
                        'poNumber' => 123
                    ],
                    StartCheckout::SETTINGS_KEY  => [
                        'allow_manual_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ]
                ],
                'checkoutSource' => (new CheckoutSourceStub())->setId(1)
            ],
            'with_force' => [
                'options' => [
                    StartCheckout::SOURCE_FIELD_KEY => 'shoppingList',
                    StartCheckout::SOURCE_ENTITY_KEY => new ShoppingList(),
                    StartCheckout::CHECKOUT_DATA_KEY => [
                        'poNumber' => 123
                    ],
                    StartCheckout::SETTINGS_KEY  => [
                        'allow_manual_source_remove' => true,
                        'disallow_billing_address_edit' => false,
                        'disallow_shipping_address_edit' => false,
                        'remove_source' => true
                    ],
                    'force' => true
                ],
                'checkoutSource' => (new CheckoutSourceStub())->setId(1),
                'force' => true
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

    protected function setUpTokenStorage()
    {
        $this->tokenStorage = $this
            ->getMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');

        $account = new Account();
        $account->setOwner(new User());
        $account->setOrganization(new Organization());
        $user = new AccountUser();
        $user->setAccount($account);

        /** @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject $token */
        $token = $this->getMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $token->expects($this->any())->method('getUser')->willReturn($user);
        $this->tokenStorage->expects($this->any())->method('getToken')->willReturn($token);
    }
}
