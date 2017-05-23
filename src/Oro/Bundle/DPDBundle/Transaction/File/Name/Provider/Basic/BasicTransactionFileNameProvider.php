<?php

namespace Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\Basic;

use Oro\Bundle\DPDBundle\Model\SetOrderResponse;
use Oro\Bundle\DPDBundle\Transaction\File\Name\Provider\TransactionFileNameProviderInterface;
use Oro\Bundle\OrderBundle\Entity\Order;
use Symfony\Component\Translation\TranslatorInterface;

class BasicTransactionFileNameProvider implements TransactionFileNameProviderInterface
{
    /**
     * @var string
     */
    private $messageId;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @param string              $messageId
     * @param TranslatorInterface $translator
     */
    public function __construct($messageId, TranslatorInterface $translator)
    {
        $this->messageId = $messageId;
        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionFileName(Order $order, SetOrderResponse $response)
    {
        return $this->translator->trans($this->messageId, [
            '%orderNumber%' => $order->getIdentifier(),
            '%parcelNumbers%' => implode(', ', $response->getParcelNumbers()),
        ]).'.pdf';
    }
}
