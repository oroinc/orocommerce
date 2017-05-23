<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Transaction\File\Name\Provider\Basic;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\Basic\BasicTransactionFileNameProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Translation\TranslatorInterface;

class BasicTransactionFileNameProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $translator;

    protected function setUp()
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
    }

    public function testGetAttachmentComment()
    {
        $messageId = 'oro_dpd.message';

        $orderIdentifier = 'orderNum';

        $order = $this->createMock(Order::class);

        $order->expects(static::once())
            ->method('getIdentifier')
            ->willReturn($orderIdentifier);

        $translatedMessage = 'File comment';

        $transaction = $this->createMock(SetOrderResponse::class);
        $transaction->expects(static::once())
            ->method('getParcelNumbers')
            ->willReturn([1, 4, '5']);

        $this->translator->expects(static::once())
            ->method('trans')
            ->with($messageId, [
                '%orderNumber%' => $orderIdentifier,
                '%parcelNumbers%' => '1, 4, 5',
            ])
            ->willReturn($translatedMessage);

        $provider = new BasicTransactionFileNameProvider($messageId, $this->translator);

        $expectedName = $translatedMessage.'.pdf';

        static::assertEquals($expectedName, $provider->getTransactionFileName($order, $transaction));
    }
}
