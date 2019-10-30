<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Doctrine\ORM\EntityNotFoundException;
use Oro\Bundle\CheckoutBundle\Action\SendOrderConfirmationEmail;
use Oro\Bundle\EmailBundle\Exception\EmailTemplateCompilationException;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action\AbstractSendEmailTemplateTest;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class SendOrderConfirmationEmailTest extends AbstractSendEmailTemplateTest
{
    /** @var SendOrderConfirmationEmail */
    private $action;

    protected function setUp(): void
    {
        parent::setUp();

        $this->action = new SendOrderConfirmationEmail(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->registry,
            $this->validator,
            $this->localizedTemplateProvider,
            $this->emailOriginHelper
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
        $this->localizedTemplateProvider->expects($this->once())
            ->method('getAggregated')
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

    /**
     * @return array
     */
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
