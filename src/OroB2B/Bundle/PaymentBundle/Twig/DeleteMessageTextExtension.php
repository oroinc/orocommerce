<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextExtension extends \Twig_Extension
{
    const CUSTOMER_GROUP_GRID_NAME = 'customer-groups-grid';
    const CUSTOMER_GRID_NAME = 'customer-customers-grid';
    const CUSTOMER_GROUP_GRID_ROUTE = 'orob2b_customer_group_index';
    const CUSTOMER_GRID_ROUTE = 'orob2b_customer_index';

    /** @var  DeleteMessageTextGenerator */
    protected $deleteMessageGenerator;

    public function __construct(DeleteMessageTextGenerator $deleteMessageGenerator)
    {
        $this->deleteMessageGenerator = $deleteMessageGenerator;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'orob2b_payment_term_delete_message';
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getPaymentTermDeleteMessageText', [$this, 'getDeleteMessageText']),
        ];
    }

    /**
     * @param PaymentTerm $paymentTerm
     * @return string
     */
    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        return $this->deleteMessageGenerator->getDeleteMessageText($paymentTerm);
    }
}
