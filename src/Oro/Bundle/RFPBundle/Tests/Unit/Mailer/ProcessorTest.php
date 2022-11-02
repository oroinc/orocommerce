<?php

namespace Oro\Bundle\RFPBundle\Tests\Unit\Mailer;

use Oro\Bundle\RFPBundle\Entity\Request;
use Oro\Bundle\RFPBundle\Mailer\Processor;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Mailer\UserTemplateEmailSender;
use PHPUnit\Framework\MockObject\MockObject;

class ProcessorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var Request
     */
    private $request;

    /**
     * @var User
     */
    private $user;

    /**
     * @var UserTemplateEmailSender|MockObject
     */
    private $userTemplateEmailSender;

    /**
     * @var Processor
     */
    private $mailProcessor;

    protected function setUp(): void
    {
        $this->request = new Request();

        $this->user = new User();
        $this->user->setEmail('user@example.com');

        $this->userTemplateEmailSender = $this->createMock(UserTemplateEmailSender::class);
        $this->mailProcessor = new Processor($this->userTemplateEmailSender);
    }

    public function testSendRFPNotification(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::CREATE_REQUEST_TEMPLATE_NAME,
                ['entity' => $this->request]
            )
            ->willReturn($returnValue);

        self::assertEquals($returnValue, $this->mailProcessor->sendRFPNotification($this->request, $this->user));
    }

    public function testSendConfirmation(): void
    {
        $returnValue = 1;
        $this->userTemplateEmailSender
            ->expects($this->once())
            ->method('sendUserTemplateEmail')
            ->with(
                $this->user,
                Processor::CONFIRM_REQUEST_TEMPLATE_NAME,
                ['entity' => $this->request]
            )
            ->willReturn($returnValue);

        self::assertEquals(
            $returnValue,
            $this->mailProcessor->sendConfirmation($this->request, $this->user)
        );
    }
}
