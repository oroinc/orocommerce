<?php

namespace OroB2B\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\WorkflowBundle\Model\Workflow;
use Oro\Bundle\WorkflowBundle\Model\WorkflowManager;

use OroB2B\Bundle\CheckoutBundle\Entity\Checkout;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use OroB2B\Bundle\CheckoutBundle\Entity\CheckoutSource;
use OroB2B\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use OroB2B\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener;
use OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CheckoutEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutEntityListener
     */
    protected $listener;

    /**
     * @var WorkflowManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $workflowManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $repository;

    /**
     * @var UserCurrencyManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $userCurrencyManager;

    /**
     * {@inheritdoc}
     */
    public function setUp()
    {
        parent::setUp();

        $this->workflowManager = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\WorkflowManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->repository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $manager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $manager->expects($this->any())
            ->method('getRepository')
            ->willReturn($this->repository);

        /** @var RegistryInterface|\PHPUnit_Framework_MockObject_MockObject $registry */
        $registry = $this->getMockBuilder('Symfony\Bridge\Doctrine\RegistryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($manager);

        $this->userCurrencyManager = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CheckoutEntityListener($this->workflowManager, $registry, $this->userCurrencyManager);
    }

    public function testCheckoutType()
    {
        $this->listener->setCheckoutType('test');
        $this->assertAttributeEquals('test', 'checkoutType', $this->listener);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Checkout class must implement OroB2B\Bundle\CheckoutBundle\Entity\CheckoutInterface
     */
    public function testCheckoutClassNameInvalid()
    {
        $this->listener->setCheckoutClassName('test');
    }

    public function testCheckoutClassName()
    {
        $className = 'OroB2B\Bundle\CheckoutBundle\Entity\Checkout';
        $this->listener->setCheckoutClassName($className);
        $this->assertAttributeEquals($className, 'checkoutClassName', $this->listener);
    }

    /**
     * @dataProvider existingCheckoutByIdProvider
     *
     * @param string $type
     * @param int $id
     * @param CheckoutInterface $found
     * @param CheckoutInterface $expected
     * @param string $userCurrency
     */
    public function testOnGetCheckoutEntityExistingById(
        $type,
        $id,
        CheckoutInterface $found = null,
        CheckoutInterface $expected = null,
        $userCurrency = 'USD'
    ) {
        $this->listener->setCheckoutClassName('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');

        $event = new CheckoutEntityEvent();

        $this->repository->expects($this->any())
            ->method('find')
            ->with($id)
            ->willReturn($found);

        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn([]);

        $event->setType($type);
        $event->setCheckoutId($id);

        if ($expected instanceof Checkout) {
            $this->userCurrencyManager->expects($this->once())
                ->method('getUserCurrency')
                ->willReturn($userCurrency);
        }

        $this->listener->onGetCheckoutEntity($event);
        $this->assertCheckoutEvent($event, $expected);
    }

    /**
     * @return array
     */
    public function existingCheckoutByIdProvider()
    {
        $checkout = new Checkout();
        $checkout->setCurrency('USD');
        return [
            'find existing by id' => [
                'type' => '',
                'id' => 1,
                'found' => $checkout,
                'expected' => $checkout,
                'userCurrency' => 'USD'
            ],
            'find existing by id with not actual currency' => [
                'type' => '',
                'id' => 1,
                'found' => $checkout,
                'expected' => $checkout->setCurrency('EUR'),
                'userCurrency' => 'EUR'
            ],
            'find existing by id another type' => [
                'type' => 'unknown',
                'id' => 1,
                'found' => $checkout,
                'expected' => null,
                'userCurrency' => 'USD'
            ],
            'find existing by id none' => [
                'type' => '',
                'id' => 1,
                'found' => null,
                'expected' => null,
                'userCurrency' => 'USD'
            ],
        ];
    }

    /**
     * @dataProvider existingCheckoutBySourceProvider
     *
     * @param CheckoutSource $source
     * @param CheckoutInterface $found
     * @param CheckoutInterface $expected
     */
    public function testOnGetCheckoutEntityExistingBySource(
        CheckoutSource $source = null,
        CheckoutInterface $found = null,
        CheckoutInterface $expected = null
    ) {
        $this->listener->setCheckoutClassName('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');

        $event = new CheckoutEntityEvent();

        $this->repository->expects($this->any())
            ->method('findOneBy')
            ->with(['source' => $source])
            ->willReturn($found);

        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn([]);

        $event->setSource($source);

        $this->listener->onGetCheckoutEntity($event);
        $this->assertCheckoutEvent($event, $expected);
    }

    /**
     * @return array
     */
    public function existingCheckoutBySourceProvider()
    {
        $checkoutSource = (new CheckoutSource())->setId(1);
        $checkout = new Checkout();
        return [
            'find existing incorrect call' => [
                'source' => null,
                'found' => $checkout,
                'expected' => null
            ],
            'find existing by source' => [
                'source' => $checkoutSource,
                'found' => $checkout,
                'expected' => $checkout
            ],
            'find existing by source none' => [
                'source' => $checkoutSource,
                'found' => null,
                'expected' => null
            ],
        ];
    }

    /**
     * @dataProvider onGetCheckoutEntityDataProviderNew
     *
     * @param bool $isStartWorkflowAllowed
     * @param array $workflows
     * @param CheckoutSource $source
     * @param CheckoutInterface $expected
     */
    public function testOnGetCheckoutEntityNew(
        $isStartWorkflowAllowed,
        array $workflows = [],
        CheckoutSource $source = null,
        CheckoutInterface $expected = null
    ) {
        $this->listener->setCheckoutClassName('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn($workflows);
        $this->workflowManager->expects($this->any())
            ->method('isStartTransitionAvailable')
            ->willReturn($isStartWorkflowAllowed);

        $event = new CheckoutEntityEvent();
        $event->setSource($source);

        $this->listener->onCreateCheckoutEntity($event);
        $this->assertCheckoutEvent($event, $expected);
    }

    /**
     * @return array
     */
    public function onGetCheckoutEntityDataProviderNew()
    {
        $checkoutSource = (new CheckoutSource())->setId(1);
        return [
            'new instance with available workflows' => [
                'isStartWorkflowAllowed' => true,
                'workflows' => [$this->getWorkflowMock('test')],
                'source' => $checkoutSource,
                'expected' => (new Checkout())->setSource($checkoutSource),
            ],
            'new instance without available workflows' => [
                'isStartWorkflowAllowed' => true,
                'workflows' => [],
                'source' => $checkoutSource,
                'expected' => null,
            ],
            'new instance start disallowed with available workflows' => [
                'isStartWorkflowAllowed' => false,
                'workflows' => [$this->getWorkflowMock('test')],
                'source' => $checkoutSource,
                'expected' => null
            ],
            'new instance start disallowed without available workflows' => [
                'isStartWorkflowAllowed' => false,
                'workflows' => [],
                'source' => $checkoutSource,
                'expected' => null
            ]
        ];
    }

    public function testOnCreateCheckoutEntityException()
    {
        $this->setExpectedException(
            'LogicException',
            'More than one active workflow found for entity "OroB2B\Bundle\CheckoutBundle\Entity\Checkout"'
        );

        $this->listener->setCheckoutClassName('OroB2B\Bundle\CheckoutBundle\Entity\Checkout');
        $this->workflowManager->expects($this->any())
            ->method('getApplicableWorkflows')
            ->willReturn([$this->getWorkflowMock('test1'), $this->getWorkflowMock('test2')]);
        $this->workflowManager->expects($this->any())
            ->method('isStartTransitionAvailable')
            ->willReturn(true);

        $event = new CheckoutEntityEvent();
        $event->setSource(new CheckoutSource());

        $this->listener->onCreateCheckoutEntity($event);
    }

    /**
     * @param string $name
     * @return Workflow|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getWorkflowMock($name)
    {
        $workflow = $this->getMockBuilder('Oro\Bundle\WorkflowBundle\Model\Workflow')
            ->disableOriginalConstructor()
            ->getMock();
        $workflow->expects($this->any())->method('getName')->willReturn($name);

        return $workflow;
    }

    /**
     * @param CheckoutEntityEvent $event
     * @param CheckoutInterface|null $expected
     */
    protected function assertCheckoutEvent(CheckoutEntityEvent $event, CheckoutInterface $expected = null)
    {
        $actual = $event->getCheckoutEntity();
        $this->assertEquals($expected, $actual);
        $this->assertEquals((bool)$expected, $event->isPropagationStopped());
    }
}
