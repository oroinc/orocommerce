<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides Twig functions to get text of the message about deletion of a payment term
 * with links to the affected customers and customer groups:
 *   - get_payment_term_delete_message
 *   - get_payment_term_delete_message_datagrid
 */
class DeleteMessageTextExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    public function __construct(
        private readonly ContainerInterface $container
    ) {
    }

    #[\Override]
    public function getFunctions()
    {
        return [
            new TwigFunction('get_payment_term_delete_message', [$this, 'getDeleteMessageText']),
            new TwigFunction('get_payment_term_delete_message_datagrid', [$this, 'getDeleteMessageDatagrid']),
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

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            DeleteMessageTextGenerator::class
        ];
    }

    private function getDeleteMessageGenerator(): DeleteMessageTextGenerator
    {
        return $this->container->get(DeleteMessageTextGenerator::class);
    }
}
