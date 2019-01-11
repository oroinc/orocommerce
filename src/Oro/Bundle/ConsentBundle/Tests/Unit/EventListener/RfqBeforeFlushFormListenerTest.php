<?php

namespace Oro\Bundle\ConsentBundle\Tests\Unit\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;
use Oro\Bundle\ConsentBundle\EventListener\RfqBeforeFlushFormListener;
use Oro\Bundle\ConsentBundle\Form\Type\ConsentAcceptanceType;
use Oro\Bundle\ConsentBundle\Tests\Unit\Entity\Stub\CustomerUserStub;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\RFPBundle\Entity\Request;
use Symfony\Component\Form\FormInterface;

class RfqBeforeFlushFormListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FeatureChecker|\PHPUnit_Framework_MockObject_MockObject */
    private $featureChecker;

    /** @var RfqBeforeFlushFormListener */
    private $listener;

    protected function setUp()
    {
        $this->featureChecker = $this->createMock(FeatureChecker::class);
        $this->listener = new RfqBeforeFlushFormListener();
    }

    public function testBeforeFlushFeatureDisabled()
    {
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('consents');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(false);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $event = new AfterFormProcessEvent($form, []);

        $form->expects($this->never())
            ->method('get');

        $this->listener->beforeFlush($event);
    }

    private function configureFeatureEnabled()
    {
        $this->listener->setFeatureChecker($this->featureChecker);
        $this->listener->addFeature('consents');

        $this->featureChecker->expects($this->once())
            ->method('isFeatureEnabled')
            ->with('consents', null)
            ->willReturn(true);
    }

    public function testBeforeFlushEmptyData()
    {
        $this->configureFeatureEnabled();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $event = new AfterFormProcessEvent($form, []);

        $form->expects($this->never())
            ->method('get');

        $this->listener->beforeFlush($event);
    }

    public function testBeforeFlushCustomerIsNotGuest()
    {
        $this->configureFeatureEnabled();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $requestForQuote = new Request();
        $customerUser = new CustomerUser();
        $requestForQuote->setCustomerUser($customerUser);
        $event = new AfterFormProcessEvent($form, $requestForQuote);

        $form->expects($this->never())
            ->method('get');

        $this->listener->beforeFlush($event);
    }

    public function testBeforeFlushSetsConsents()
    {
        $this->configureFeatureEnabled();

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->createMock(FormInterface::class);

        $requestForQuote = new Request();
        $customerUser = (new CustomerUserStub())->setIsGuest(true);
        $requestForQuote->setCustomerUser($customerUser);
        $event = new AfterFormProcessEvent($form, $requestForQuote);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $consentFieldForm = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('get')
            ->with(ConsentAcceptanceType::TARGET_FIELDNAME)
            ->willReturn($consentFieldForm);

        $consents = new ArrayCollection([new ConsentAcceptance()]);
        $consentFieldForm->expects($this->once())
            ->method('getData')
            ->willReturn($consents);

        $this->listener->beforeFlush($event);

        $this->assertSame($consents, $customerUser->getAcceptedConsents());
    }
}
