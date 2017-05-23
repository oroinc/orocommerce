<?php

namespace Oro\Bundle\DPDBundle\Tests\Unit\Order\Shipping\Attachment\Comment\Provider\Basic;

use Oro\Bundle\DPDBundle\Entity\DPDTransaction;
use Oro\Bundle\DPDBundle\Order\Shipping\Attachment\Comment\Provider\Basic\BasicOrderShippingAttachmentCommentProvider;
use Symfony\Component\Translation\TranslatorInterface;

class BasicOrderShippingAttachmentCommentProviderTest extends \PHPUnit_Framework_TestCase
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

        $translatedMessage = 'File comment';

        $transaction = $this->createMock(DPDTransaction::class);
        $transaction->expects(static::once())
            ->method('getParcelNumbers')
            ->willReturn([1, 4, '5']);

        $this->translator->expects(static::once())
            ->method('trans')
            ->with($messageId, ['%parcelNumbers%' => '1, 4, 5'])
            ->willReturn($translatedMessage);

        $provider = new BasicOrderShippingAttachmentCommentProvider($messageId, $this->translator);

        static::assertEquals($translatedMessage, $provider->getAttachmentComment($transaction));
    }
}
