<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomer;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about price lists assigned to customer/website.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class CustomerPriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ManagerRegistry $registry,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function getPriceListAssignments(): ?array
    {
        $customer = $this->requestHandler->getCustomer();
        if (!$customer) {
            return null;
        }

        $website = $this->requestHandler->getWebsite();

        $priceLists = $this->registry->getRepository(PriceListToCustomer::class)
            ->findBy(
                [
                    'customer' => $customer,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            );

        /** @var PriceListCustomerFallback $fallbackEntity */
        $fallbackEntity = $this->registry->getRepository(PriceListCustomerFallback::class)
            ->findOneBy([
                'customer' => $customer,
                'website' => $website
            ]);

        $fallbackChoices = [
            PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY =>
                'oro.pricing.fallback.current_customer_only.label',
            PriceListCustomerFallback::ACCOUNT_GROUP =>
                'oro.pricing.fallback.customer_group.label',
        ];

        $fallback = $fallbackEntity
            ? $fallbackChoices[$fallbackEntity->getFallback()]
            : $fallbackChoices[PriceListCustomerFallback::ACCOUNT_GROUP];

        $isCurrentOnly = $fallbackEntity?->getFallback() === PriceListCustomerFallback::CURRENT_ACCOUNT_ONLY;
        $link = null;
        if ($this->authorizationChecker->isGranted('VIEW', $customer)) {
            $link = $this->urlGenerator->generate('oro_customer_customer_view', ['id' => $customer->getId()]);
        }

        return [
            'section_title' => $this->translator->trans('oro.customer.customer.entity_label'),
            'link' => $link,
            'link_title' => $customer->getName(),
            'fallback' => $fallback,
            'fallback_entity_title' => $isCurrentOnly ? null : $customer->getGroup()?->getName(),
            'price_lists' => $priceLists,
            'stop' => $isCurrentOnly
        ];
    }
}
