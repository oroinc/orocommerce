<?php

namespace OroB2B\Bundle\PaymentBundle\Twig;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;

class DeleteMessageTextExtension extends \Twig_Extension
{
    const DELETE_MESSAGE_TEXT_EXTENSION_NAME = 'orob2b_payment_term_delete_message';

    /** @var  DeleteMessageTextGenerator */
    protected $deleteMessageGenerator;

    /**
     * @param DeleteMessageTextGenerator $deleteMessageGenerator
     */
    public function __construct(DeleteMessageTextGenerator $deleteMessageGenerator)
    {
        $this->deleteMessageGenerator = $deleteMessageGenerator;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::DELETE_MESSAGE_TEXT_EXTENSION_NAME;
    }

    /**
     * @return array
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_payment_term_delete_message', [$this, 'getDeleteMessageText']),
            new \Twig_SimpleFunction('get_payment_term_delete_message_datagrid', [$this, 'getDeleteMessageDatagrid']),
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

    /**
     * @param $paymentTermId
     * @return string
     */
    public function getDeleteMessageDatagrid($paymentTermId)
    {
        return $this->deleteMessageGenerator->getDeleteMessageTextForDataGrid($paymentTermId);
    }
}
