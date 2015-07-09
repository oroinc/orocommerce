<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTermManager;

class DeleteMessageTextExtension extends \Twig_Extension
{
    /** @var PaymentTermManager  */
    protected $paymentTermManager;

    public function __construct(PaymentTermManager $paymentTermManager)
    {
        $this->paymentTermManager = $paymentTermManager;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        'orob2b_payment_term_delete_message';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('getPaymentTermDeleteMessageText', [$this, 'getDeleteMessageText']),
        ];
    }

    public function getDeleteMessageText(PaymentTerm $paymentTerm)
    {
        return $this->paymentTermManager->getDeleteMessageText($paymentTerm);
    }
}
