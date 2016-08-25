<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Symfony\Bridge\Doctrine\RegistryInterface;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutEntityEvent;
use Oro\Bundle\CheckoutBundle\EventListener\CheckoutEntityListener;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;

class CheckoutEntityListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CheckoutEntityListener
     */
    protected $listener;

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

        $this->userCurrencyManager = $this->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new CheckoutEntityListener($registry, $this->userCurrencyManager);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Checkout class must implement Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface
     */
    public function testCheckoutClassNameInvalid()
    {
        $this->listener->setCheckoutClassName('test');
    }

    public function testCheckoutClassName()
    {
        $className = 'Oro\Bundle\CheckoutBundle\Entity\Checkout';
        $this->listener->setCheckoutClassName($className);
        $this->assertAttributeEquals($className, 'checkoutClassName', $this->listener);
    }

    /**
     * @dataProvider existingCheckoutByIdProvider
     *
     * @param int $id
     * @param CheckoutInterface $found
     * @param CheckoutInterface $expected
     * @param string $userCurrency
     */
    public function testOnGetCheckoutEntityExistingById(
        $id,
        CheckoutInterface $found = null,
        CheckoutInterface $expected = null,
        $userCurrency = 'USD'
    ) {
        $this->listener->setCheckoutClassName('Oro\Bundle\CheckoutBundle\Entity\Checkout');

        $event = new CheckoutEntityEvent();

        $this->repository->expects($this->any())
            ->method('find')
            ->with($id)
            ->willReturn($found);

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
                'id' => 1,
                'found' => $checkout,
                'expected' => $checkout,
                'userCurrency' => 'USD'
            ],
            'find existing by id with not actual currency' => [
                'id' => 1,
                'found' => $checkout,
                'expected' => $checkout->setCurrency('EUR'),
                'userCurrency' => 'EUR'
            ],
            'find existing by id none' => [
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
        $this->listener->setCheckoutClassName('Oro\Bundle\CheckoutBundle\Entity\Checkout');

        $event = new CheckoutEntityEvent();

        $this->repository->expects($this->any())
            ->method('findOneBy')
            ->with(['source' => $source])
            ->willReturn($found);

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
     * @param CheckoutSource $source
     * @param CheckoutInterface $expected
     */
    public function testOnGetCheckoutEntityNew(
        CheckoutSource $source = null,
        CheckoutInterface $expected = null
    ) {
        $this->listener->setCheckoutClassName('Oro\Bundle\CheckoutBundle\Entity\Checkout');

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
            'without source' => [
                'source' => null,
                'expected' => null,
            ],
            'new instance' => [
                'source' => $checkoutSource,
                'expected' => (new Checkout())->setSource($checkoutSource),
            ]
        ];
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
