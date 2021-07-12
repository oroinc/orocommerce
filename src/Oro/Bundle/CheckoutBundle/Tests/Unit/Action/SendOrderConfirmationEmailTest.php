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
use Twig\Error\RuntimeError;

class SendOrderConfirmationEmailTest extends \PHPUnit\Framework\TestCase
{
    private ContextAccessor|\PHPUnit\Framework\MockObject\MockObject $contextAccessor;

    private Processor|\PHPUnit\Framework\MockObject\MockObject $emailProcessor;

    private EntityNameResolver|\PHPUnit\Framework\MockObject\MockObject $entityNameResolver;

    private ValidatorInterface|\PHPUnit\Framework\MockObject\MockObject $validator;

    private AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject $sender;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    protected EventDispatcher|\PHPUnit\Framework\MockObject\MockObject $dispatcher;

    private SendOrderConfirmationEmail $action;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock(ContextAccessor::class);
        $this->contextAccessor->expects(self::any())
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
     *
     * @param \Throwable $exception
     * @param string $logMessage
     */
    public function testExecuteIgnoresExceptions(\Throwable $exception, string $logMessage): void
    {
        $this->sender->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->logger->expects(self::once())
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

    /**
     * @return array
     */
    public function executeIgnoresExceptionsDataProvider(): array
    {
        return [
            [
                'exception' => new RuntimeError('Twig_Error_Runtime'),
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
