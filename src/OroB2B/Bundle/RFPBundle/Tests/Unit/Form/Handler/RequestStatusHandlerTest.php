<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\RFPBundle\Form\Handler\RequestStatusHandler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

class RequestStatusHandlerTest extends FormHandlerTestCase
{
    const LOCALE = 'uk_UA';

    /**
     * @var RequestStatusHandler
     */
    protected $handler;

    /**
     * @var \Symfony\Component\Translation\TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity = $this->getMockBuilder('OroB2B\Bundle\RFPBundle\Entity\RequestStatus')
            ->disableOriginalConstructor()
            ->getMock();

        $this->entity->expects($this->any())
            ->method('getId')
            ->willReturn(42);

        $this->handler = new RequestStatusHandler($this->form, $this->request, $this->manager, $this->translator);
    }

    /**
     * Test setDefaultLocale
     */
    public function testSetDefaultLocale()
    {
        $this->entity->expects($this->once(0))
            ->method('setLocale')
            ->with(static::LOCALE)
            ->willReturnSelf();

        $this->handler->setDefaultLocale(static::LOCALE);

        $this->handler->process($this->entity);
    }
}
