<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CheckoutBundle\Action\SendOrderConfirmationEmail;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tools\AggregatedEmailTemplatesSender;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EntityBundle\Provider\EntityNameResolver;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Twig\Error\RuntimeError;

class SendOrderConfirmationEmailTest extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    private AggregatedEmailTemplatesSender|\PHPUnit\Framework\MockObject\MockObject $aggregatedEmailTemplatesSender;

    private SendOrderConfirmationEmail $action;

    protected function setUp(): void
    {
        $contextAccessor = $this->createMock(ContextAccessor::class);
        $contextAccessor->expects(self::any())
            ->method('getValue')
            ->willReturnArgument(1);

        $entityNameResolver = $this->createMock(EntityNameResolver::class);
        $validator = $this->createMock(ValidatorInterface::class);
        $this->aggregatedEmailTemplatesSender = $this->createMock(AggregatedEmailTemplatesSender::class);

        $dispatcher = $this->createMock(EventDispatcher::class);

        $this->action = new SendOrderConfirmationEmail(
            $contextAccessor,
            $validator,
            new EmailAddressHelper(),
            $entityNameResolver,
            $this->aggregatedEmailTemplatesSender
        );

        $this->action->setDispatcher($dispatcher);

        $this->setUpLoggerMock($this->action);
    }

    /**
     * @dataProvider executeIgnoresExceptionsDataProvider
     */
    public function testExecuteIgnoresExceptions(\Throwable $exception, string $logMessage): void
    {
        $this->aggregatedEmailTemplatesSender->expects(self::once())
            ->method('send')
            ->willThrowException($exception);

        $this->loggerMock->expects(self::once())
            ->method('error')
            ->with($logMessage, ['exception' => $exception]);

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
