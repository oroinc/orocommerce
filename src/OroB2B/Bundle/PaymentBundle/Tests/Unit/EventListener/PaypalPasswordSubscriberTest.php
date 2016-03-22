<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\PaymentBundle\EventListener\PaypalPasswordSubscriber;

class PaypalPasswordSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaypalPasswordSubscriber */
    protected $subscriber;

    protected function setUp()
    {
        $this->subscriber = new PaypalPasswordSubscriber();
    }

    protected function tearDown()
    {
        unset($this->subscriber);
    }

    public function testPreSetData()
    {
        /** @var FormInterface $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $data = 'some data';
        $event = new FormEvent($form, $data);
        $this->subscriber->preSetData($event);
        $this->assertEquals(PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER, $event->getData());
    }

    /**
     * @param bool $placeholderPresent
     * @dataProvider preSubmitProvider
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function testPreSubmit($placeholderPresent)
    {
        $formName = 'formName';

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $parentForm */
        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $parentForm
            ->expects($placeholderPresent ? $this->once() : $this->never())
            ->method('remove')
            ->with($formName);

        /** @var FormInterface|\PHPUnit_Framework_MockObject_MockObject $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form
            ->expects($this->once())
            ->method('getParent')
            ->willReturn($parentForm);

        $form
            ->expects($placeholderPresent ? $this->once() : $this->never())
            ->method('getName')
            ->willReturn($formName);

        $data = $placeholderPresent ? PaypalPasswordSubscriber::PASSWORD_PLACEHOLDER : 'some_password';
        $event = new FormEvent($form, $data);
        $this->subscriber->preSubmit($event);

        $this->assertEquals($data, $event->getData());
    }

    /**
     * @return array
     */
    public function preSubmitProvider()
    {
        return [
            'some password' => [
                'placeholderPresent' => false,
            ],
            'placeholder as password' => [
                'placeholderPresent' => true,
            ],
        ];
    }

    public function testGetSubscribedEvents()
    {
        $subscribedEvents = $this->subscriber->getSubscribedEvents();
        $this->assertEquals('preSetData', $subscribedEvents[FormEvents::PRE_SET_DATA]);
        $this->assertEquals('preSubmit', $subscribedEvents[FormEvents::PRE_SUBMIT]);
    }
}
