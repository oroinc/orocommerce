<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;

class DeleteMessageTextExtension extends \Twig_Extension
{
    const DELETE_MESSAGE_TEXT_EXTENSION_NAME = 'oro_payment_term_delete_message';

    /** @var ContainerInterface */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return DeleteMessageTextGenerator
     */
    protected function getDeleteMessageGenerator()
    {
        return $this->container->get('oro_payment_term.payment_term.delete_message_generator');
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
        return $this->getDeleteMessageGenerator()->getDeleteMessageText($paymentTerm);
    }

    /**
     * @param $paymentTermId
     * @return string
     */
    public function getDeleteMessageDatagrid($paymentTermId)
    {
        return $this->getDeleteMessageGenerator()->getDeleteMessageTextForDataGrid($paymentTermId);
    }
}
