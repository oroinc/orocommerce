<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Mailer;

use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Tests\Unit\Mailer\AbstractProcessorTest;

use OroB2B\Bundle\RFPBundle\Entity\Request;
use OroB2B\Bundle\RFPBundle\Mailer\Processor;

class ProcessorTest extends AbstractProcessorTest
{
    /**
     * @var Processor|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mailProcessor;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var User
     */
    protected $user;

    protected function setUp()
    {
        parent::setUp();

        $this->request = new Request();

        $this->user = new User();
        $this->user->setEmail('user@example.com');

        $this->mailProcessor = new Processor(
            $this->managerRegistry,
            $this->configManager,
            $this->renderer,
            $this->emailHolderHelper,
            $this->mailer
        );
    }

    protected function tearDown()
    {
        parent::tearDown();

        unset($this->user, $this->request);
    }

    public function testSendRFPNotification()
    {
        $this->assertSendCalled(
            Processor::CREATE_REQUEST_TEMPLATE_NAME,
            ['entity' => $this->request],
            $this->buildMessage($this->user->getEmail())
        );

        $this->mailProcessor->sendRFPNotification($this->request, $this->user);
    }
}
