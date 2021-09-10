<?php

namespace Oro\Bundle\PaymentTermBundle\Twig;

use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to get payment term from an entity:
 *   - get_payment_term
 */
class PaymentTermExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('get_payment_term', [$this, 'getPaymentTerm'])
        ];
    }

    /**
     * @param object $object
     *
     * @return PaymentTerm|null
     */
    public function getPaymentTerm($object)
    {
        return $this->getPaymentTermProvider()->getObjectPaymentTerm($object);
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return [
            'oro_payment_term.provider.payment_term' => PaymentTermProviderInterface::class,
        ];
    }

    private function getPaymentTermProvider(): PaymentTermProviderInterface
    {
        return $this->container->get('oro_payment_term.provider.payment_term');
    }
}
