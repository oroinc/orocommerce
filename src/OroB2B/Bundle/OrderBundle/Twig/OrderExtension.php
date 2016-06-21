<?php

namespace OroB2B\Bundle\OrderBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Formatter\SourceDocumentFormatter;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentStatusProvider;

class OrderExtension extends \Twig_Extension
{
    const NAME = 'orob2b_order_order';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var SourceDocumentFormatter
     */
    protected $sourceDocumentFormatter;

    /**
     * @var PaymentStatusProvider
     */
    protected $paymentStatusProvider;

    /**
     * @param ManagerRegistry $registry
     * @param SourceDocumentFormatter $sourceDocumentFormatter
     * @param PaymentStatusProvider $paymentStatusProvider
     */
    public function __construct(
        ManagerRegistry $registry,
        SourceDocumentFormatter $sourceDocumentFormatter,
        PaymentStatusProvider $paymentStatusProvider
    ) {
        $this->registry = $registry;
        $this->sourceDocumentFormatter = $sourceDocumentFormatter;
        $this->paymentStatusProvider = $paymentStatusProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new \Twig_SimpleFilter(
                'orob2b_order_format_source_document',
                [$this, 'formatSourceDocument'],
                ['is_safe' => ['html']]
            )
        ];
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_payment_status_label', [$this, 'getPaymentStatusLabel'])
        ];
    }

    /**
     * @param string $sourceEntityClass
     * @param int $sourceEntityId
     * @param string $sourceEntityIdentifier
     *
     * @return string
     */
    public function formatSourceDocument($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier)
    {
        return $this->sourceDocumentFormatter->format($sourceEntityClass, $sourceEntityId, $sourceEntityIdentifier);
    }

    /**
     * @param int $orderId
     * @return string
     */
    public function getPaymentStatusLabel($orderId)
    {
        /** @var Order $order */
        $order = $this->registry
            ->getManagerForClass('OroB2BOrderBundle:Order')
            ->getRepository('OroB2BOrderBundle:Order')
            ->find($orderId);

        return $this->paymentStatusProvider->getPaymentStatus($order);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
