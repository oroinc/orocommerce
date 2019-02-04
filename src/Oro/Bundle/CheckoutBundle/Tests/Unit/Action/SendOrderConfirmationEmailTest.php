<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Action;

use Oro\Bundle\CheckoutBundle\Action\SendOrderConfirmationEmail;
use Oro\Bundle\EmailBundle\Model\EmailTemplateCriteria;
use Oro\Bundle\EmailBundle\Tests\Unit\Workflow\Action\AbstractSendEmailTemplateTest;
use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;

class SendOrderConfirmationEmailTest extends AbstractSendEmailTemplateTest
{
    protected function setUp()
    {
        $this->createDependencyMocks();

        $this->action = new SendOrderConfirmationEmail(
            $this->contextAccessor,
            $this->emailProcessor,
            new EmailAddressHelper(),
            $this->entityNameResolver,
            $this->renderer,
            $this->objectManager,
            $this->validator
        );

        $this->action->setLogger($this->logger);
        $this->action->setPreferredLanguageProvider($this->languageProvider);
        $this->action->setDispatcher($this->dispatcher);
    }

    public function testExecuteIgnoresTwigExceptions()
    {
        ['context' => $context, 'language' => $language, 'options' => $options] = $this->configureMocks();

        $this->objectRepository
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $language)
            ->willReturn($this->emailTemplate);

        $this->renderer
            ->method('compileMessage')
            ->will($this->throwException(new \Twig_Error_Runtime('Twig_Error_Runtime')));

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Twig exception in @send_order_confirmation_email action');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    public function testExecuteIgnoresMissingEmailTemplate()
    {
        ['context' => $context, 'language' => $language, 'options' => $options] = $this->configureMocks();

        $this->objectRepository
            ->method('findOneLocalized')
            ->with(new EmailTemplateCriteria($options['template'], get_class($options['entity'])), $language)
            ->willReturn(null);

        $this->logger->expects($this->at(1)) // $this->at(0) would be logging of the original exception
            ->method('error')
            ->with('Cannot find the specified email template in  @send_order_confirmation_email action');

        $this->action->initialize($options);
        $this->action->execute($context);
    }

    /**
     * @return array
     */
    private function configureMocks(): array
    {
        $context = [];

        $language = 'de';

        $options = [
            'from' => 'test@test.com',
            'to' => 'test@test.com',
            'template' => 'test',
            'subject' => 'subject',
            'body' => 'body',
            'entity' => new \stdClass(),
            'workflow' => 'test'
        ];
        $this->expectsEntityClass($options['entity']);
        $this->contextAccessor
            ->method('getValue')
            ->will($this->returnArgument(1));
        $this->entityNameResolver
            ->method('getName')
            ->will(
                $this->returnCallback(
                    function () {
                        return '_Formatted';
                    }
                )
            );

        $this->languageProvider
            ->method('getPreferredLanguage')
            ->with($options['to'])
            ->willReturn($language);

        $this->emailTemplate
            ->method('getType')
            ->willReturn('txt');

        return ['context' => $context, 'language' => $language, 'options' => $options];
    }
}
