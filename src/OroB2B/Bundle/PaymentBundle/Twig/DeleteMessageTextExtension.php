<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextExtension extends \Twig_Extension
{
    const ACCOUNT_GROUP_GRID_NAME = 'account-groups-grid';
    const ACCOUNT_GRID_NAME = 'account-accounts-grid';
    const ACCOUNT_GROUP_GRID_ROUTE = 'orob2b_account_group_index';
    const ACCOUNT_GRID_ROUTE = 'orob2b_account_index';

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
            new \Twig_SimpleFunction('getPaymentTermDeleteMessageDatagrid', [$this, 'getDeleteMessageDatagrid']),
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

    public function getDeleteMessageDatagrid($paymentTermId)
    {
        return $this->deleteMessageGenerator->getDeleteMessageTextForDataGrid($paymentTermId);
    }
}
