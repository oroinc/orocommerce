<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Handler;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\Form\Handler\RequestStatusHandler;

use Oro\Component\Testing\Unit\FormHandlerTestCase;

class RequestStatusHandlerTest extends FormHandlerTestCase
{
    /**
     * @var RequestStatusHandler
     */
    protected $handler;

    /**
     * @var Symfony\Component\Translation\TranslatorInterface
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

        $this->entity = new RequestStatus();

        $this->handler = new RequestStatusHandler($this->form, $this->request, $this->manager, $this->translator);
    }
}
