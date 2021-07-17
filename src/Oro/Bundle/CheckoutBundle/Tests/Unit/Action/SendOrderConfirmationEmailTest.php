<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CheckoutBundle\Action\SendOrderConfirmationEmail;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Mailer\Processor;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SendOrderConfirmationEmailTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $contextAccessor;

    /** @var Processor|\PHPUnit\Framework\MockObject\MockObject */
    private $emailProcessor;

    /** @var EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject */
    private $entityNameResolver;

    /** @var ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $validator;

    /** @var AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject */
    private $sender;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    /** @var EventDispatcher|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var SendOrderConfirmationEmail */
    private $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects($this->any())
            ->method('getValue')
            ->willReturnArgument(1);

        $this->emailProcessor = $this->createMock(Processor::class);
        $this->entityNameResolver = $this->createMock(EntityNameResolver::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->sender = $this->createMock(AggregatedEmailTemplatesSender::class);

        $this->logger = $this->createMock(LoggerInterface::class);
        $this->dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new SendOrderConfirmationEmail(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->validator,
            $this->sender
        );

        $this->action->setLogger($this->logger);
        $this->action->setDispatcher($this->dispatcher);
    }

    /**
     * @dataProvider executeIgnoresExceptionsDataProvider
     */
    public function testExecuteIgnoresExceptions(\Throwable $exception, string $logMessage): void
    {
        $this->sender->expects($this->once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects($this->once())
            ->method('error')
            ->with($logMessage);

        $this->action->initialize(
            [
                'from' => 'test@test.com',
                'to' => 'test@test.com',
                'template' => 'test',
                'subject' => 'subject',
                'body' => 'body',
                'entity' => new \stdClass(),
                'workflow' => 'test',
            ]
        );
        $this->action->execute([]);
    }

    public function executeIgnoresExceptionsDataProvider(): array
    {
        return [
            [
                'exception' => new \Twig_Error_Runtime('Twig_Error_Runtime'),
                'logMessage' => 'Twig exception in @send_order_confirmation_email action',
            ],
            [
                'exception' => new EmailTemplateCompilationException(new EmailTemplateCriteria('test', 'test')),
                'logMessage' => 'Twig exception in @send_order_confirmation_email action',
            ],
            [
                'exception' => new EntityNotFoundException(),
                'logMessage' => 'Cannot find the specified email template in @send_order_confirmation_email action',
            ],
        ];
    }
}
