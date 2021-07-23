<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextExtension;
use Oro\Bundle\PaymentTermBundle\Twig\DeleteMessageTextGenerator;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class DeleteMessageTextExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var DeleteMessageTextGenerator|\PHPUnit\Framework\MockObject\MockObject */
    private $deleteMessageTextGenerator;

    /** @var DeleteMessageTextExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->deleteMessageTextGenerator = $this->createMock(DeleteMessageTextGenerator::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment_term.payment_term.delete_message_generator', $this->deleteMessageTextGenerator)
            ->getContainer($this);

        $this->extension = new DeleteMessageTextExtension($container);
    }

    public function testGetDeleteMessageText()
    {
        $message = 'Delete message for payment term';
        $paymentTerm = new PaymentTerm();

        $this->deleteMessageTextGenerator->expects($this->once())
            ->method('getDeleteMessageText')
            ->with(self::identicalTo($paymentTerm))
            ->willReturn($message);

        $this->assertEquals(
            $message,
            self::callTwigFunction($this->extension, 'get_payment_term_delete_message', [$paymentTerm])
        );
    }

    public function testGetDeleteMessageDatagrid()
    {
        $message = 'Payment term delete message for datagrid';
        $paymentTermId = 1;

        $this->deleteMessageTextGenerator->expects($this->once())
            ->method('getDeleteMessageTextForDataGrid')
            ->with($paymentTermId)
            ->willReturn($message);

        $this->assertEquals(
            $message,
            self::callTwigFunction($this->extension, 'get_payment_term_delete_message_datagrid', [$paymentTermId])
        );
    }
}
