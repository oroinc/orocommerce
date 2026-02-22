<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteMessageTextExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private DeleteMessageTextGenerator&MockObject $deleteMessageTextGenerator;
    private DeleteMessageTextExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->deleteMessageTextGenerator = $this->createMock(DeleteMessageTextGenerator::class);

        $container = self::getContainerBuilder()
            ->add(DeleteMessageTextGenerator::class, $this->deleteMessageTextGenerator)
            ->getContainer($this);

        $this->extension = new DeleteMessageTextExtension($container);
    }

    public function testGetDeleteMessageText()
    {
        $message = 'Delete message for payment term';
        $paymentTerm = new PaymentTerm();

        $this->deleteMessageTextGenerator->expects(self::once())
            ->method('getDeleteMessageText')
            ->with(self::identicalTo($paymentTerm))
            ->willReturn($message);

        self::assertEquals(
            $message,
            self::callTwigFunction($this->extension, 'get_payment_term_delete_message', [$paymentTerm])
        );
    }

    public function testGetDeleteMessageDatagrid()
    {
        $message = 'Payment term delete message for datagrid';
        $paymentTermId = 1;

        $this->deleteMessageTextGenerator->expects(self::once())
            ->method('getDeleteMessageTextForDataGrid')
            ->with($paymentTermId)
            ->willReturn($message);

        self::assertEquals(
            $message,
            self::callTwigFunction($this->extension, 'get_payment_term_delete_message_datagrid', [$paymentTermId])
        );
    }
}
