<?php

namespace Oro\Bundle\OrderBundle\Twig;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShippingBundle\Translator\ShippingMethodLabelTranslator;
use Psr\Container\ContainerInterface;
use Symfony\Contracts\Service\ServiceSubscriberInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Provides a Twig function to display the name of a shipping method:
 *   - oro_order_shipping_method_label
 */
class OrderShippingExtension extends AbstractExtension implements ServiceSubscriberInterface
{
    private ContainerInterface $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    #[\Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'oro_order_shipping_method_label',
                [$this, 'getShippingMethodLabel']
            ),
        ];
    }

    public function getShippingMethodLabel(
        ?string $shippingMethod,
        ?string $shippingMethodType,
        Organization|int|null $organization = null
    ): string {
        return $this->getShippingMethodLabelTranslator()->getShippingMethodWithTypeLabel(
            $shippingMethod,
            $shippingMethodType,
            \is_int($organization) ? $this->getOrganization($organization) : $organization
        );
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            'oro_shipping.translator.shipping_method_label' => ShippingMethodLabelTranslator::class,
            DoctrineHelper::class
        ];
    }

    private function getShippingMethodLabelTranslator(): ShippingMethodLabelTranslator
    {
        return $this->container->get('oro_shipping.translator.shipping_method_label');
    }

    private function getDoctrineHelper(): DoctrineHelper
    {
        return $this->container->get(DoctrineHelper::class);
    }

    private function getOrganization(int $organizationId): Organization
    {
        return $this->getDoctrineHelper()->getEntityReference(Organization::class, $organizationId);
    }
}
