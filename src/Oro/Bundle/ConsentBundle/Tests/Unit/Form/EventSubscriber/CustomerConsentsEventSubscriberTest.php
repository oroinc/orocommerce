<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\Form\EventSubscriber;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\Extractor\CustomerUserExtractor;
use Oro\Bundle\ConsentBundle\Form\EventSubscriber\CustomerConsentsEventSubscriber;
use Oro\Bundle\ConsentBundle\Form\Type\CustomerConsentsType;
use Oro\Bundle\ConsentBundle\Handler\SaveConsentAcceptanceHandler;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class CustomerConsentsEventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var SaveConsentAcceptanceHandler|\PHPUnit_Framework_MockObject_MockObject */
    private $saveConsentAcceptanceHandler;

    /** @var CustomerConsentsEventSubscriber */
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
        $this->saveConsentAcceptanceHandler = $this->createMock(SaveConsentAcceptanceHandler::class);

        $this->customerUserExtractor = new CustomerUserExtractor();
        $this->customerUserExtractor->addMapping(Checkout::class, 'customerUser');
        $this->customerUserExtractor->addMapping(Checkout::class, 'registeredCustomerUser');

        $this->subscriber = new CustomerConsentsEventSubscriber(
            $this->customerUserExtractor,
            $this->saveConsentAcceptanceHandler
        );
        $this->mainForm = $this->createMock(FormInterface::class);
    }

    public function testSaveConsentAcceptancesNoCustomerUserInEventData()
    {
        $this->mainForm->expects($this->never())
            ->method('has');

        $this->mainForm->expects($this->never())
            ->method('isValid');

        $this->mainForm->expects($this->never())
            ->method('get');

        $this->saveConsentAcceptanceHandler->expects($this->never())
            ->method('save');

        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, null));
    }

    public function testSaveConsentAcceptancesNoCustomerConsentsField()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(false);

        $this->mainForm->expects($this->never())
            ->method('isValid');

        $this->mainForm->expects($this->never())
            ->method('get');

        $this->saveConsentAcceptanceHandler->expects($this->never())
            ->method('save');

        $eventData = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, $eventData));
    }

    public function testSaveConsentAcceptancesFormNotValid()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $this->mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $this->mainForm->expects($this->never())
            ->method('get');

        $this->saveConsentAcceptanceHandler->expects($this->never())
            ->method('save');

        $eventData = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, $eventData));
    }

    public function testSaveConsentAcceptancesNoConsentAcceptances()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $this->mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $customerConsentsField = $this->createMock(FormInterface::class);
        $this->mainForm->expects($this->once())
            ->method('get')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn($customerConsentsField);

        $customerConsentsField->expects($this->once())
            ->method('getData')
            ->willReturn(null);

        $this->saveConsentAcceptanceHandler->expects($this->never())
            ->method('save');

        $eventData = $this->getEntity(CustomerUser::class, ['id' => 1]);
        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, $eventData));
    }

    public function testSaveConsentAcceptances()
    {
        $this->mainForm->expects($this->once())
            ->method('has')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn(true);

        $this->mainForm->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $customerConsentsField = $this->createMock(FormInterface::class);
        $this->mainForm->expects($this->once())
            ->method('get')
            ->with(CustomerConsentsType::TARGET_FIELDNAME)
            ->willReturn($customerConsentsField);

        $consentAcceptance = $this->getEntity(ConsentAcceptance::class, ['id' => 1]);
        $customerConsentsField->expects($this->once())
            ->method('getData')
            ->willReturn([$consentAcceptance]);

        $customerUser = $this->getEntity(CustomerUser::class, ['id' => 1]);

        $this->saveConsentAcceptanceHandler->expects($this->once())
            ->method('save')
            ->with($customerUser, [$consentAcceptance]);

        $this->subscriber->saveConsentAcceptances(new FormEvent($this->mainForm, $customerUser));
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [FormEvents::POST_SUBMIT => ['saveConsentAcceptances', -10]],
            CustomerConsentsEventSubscriber::getSubscribedEvents()
        );
    }
}
