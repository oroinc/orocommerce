<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\FillConsentContextEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Helper\ConsentContextInitializeHelperInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Test\FormInterface;

class FillConsentContextEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var ConsentContextInitializeHelperInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $contextInitializeHelper;

    /** @var FillConsentContextEventSubscriber */
    private $subscriber;

    /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $mainForm;

    /** @var CustomerUserExtractor */
    protected $customerUserExtractor;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->contextInitializeHelper = $this->createMock(ConsentContextInitializeHelperInterface::class);

        $this->customerUserExtractor = new CustomerUserExtractor();
        $this->customerUserExtractor->addMapping(Checkout::class, 'customerUser');
        $this->customerUserExtractor->addMapping(Checkout::class, 'registeredCustomerUser');

        $this->subscriber = new FillConsentContextEventSubscriber(
            $this->contextInitializeHelper,
            $this->customerUserExtractor
        );
        $this->mainForm = $this->createMock(FormInterface::class);
    }

    public function testFillConsentContextNoCustomerUserInEventData()
    {
        $this->mainForm->expects($this->never())
            ->method('has');

        $this->contextInitializeHelper->expects($this->never())
            ->method('initialize');

        $this->subscriber->fillConsentContext(new FormEvent($this->mainForm, null));
    }

    public function testFillConsentContextNoCustomerConsentsField()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(false);

        $this->contextInitializeHelper->expects($this->never())
            ->method('initialize');

        $eventData = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $this->subscriber->fillConsentContext(new FormEvent($this->mainForm, $eventData));
    }

    public function testFillConsentContext()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $this->contextInitializeHelper->expects($this->once())
            ->method('initialize')
            ->with($customerUser);

        $this->subscriber->fillConsentContext(new FormEvent($this->mainForm, $customerUser));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SET_DATA => ['fillConsentContext', 1000]],
            FillConsentContextEventSubscriber::getSubscribedEvents()
        );
    }
}
