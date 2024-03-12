<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\PricingBundle\Debug\Handler\DebugProductPricesPriceListRequestHandler;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Form\Type\PriceListCollectionType;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provide information about price lists assigned to customer group/website.
 *
 * @internal This service is applicable for pricing debug purpose only.
 */
class CustomerGroupPriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    public function __construct(
        private DebugProductPricesPriceListRequestHandler $requestHandler,
        private ManagerRegistry $registry,
        private TranslatorInterface $translator,
        private UrlGeneratorInterface $urlGenerator,
        private CustomerUserRelationsProvider $relationsProvider,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {
    }

    public function getPriceListAssignments(): ?array
    {
        $customer = $this->requestHandler->getCustomer();
        if ($customer) {
            $customerGroup = $customer->getGroup();
        } else {
            $customerGroup = $this->relationsProvider->getCustomerGroup();
        }

        if (!$customerGroup) {
            return null;
        }

        $website = $this->requestHandler->getWebsite();

        $priceLists = $this->registry->getRepository(PriceListToCustomerGroup::class)
            ->findBy(
                [
                    'customerGroup' => $customerGroup,
                    'website' => $website
                ],
                ['sortOrder' => PriceListCollectionType::DEFAULT_ORDER]
            );

        /** @var PriceListCustomerGroupFallback $fallbackEntity */
        $fallbackEntity = $this->registry->getRepository(PriceListCustomerGroupFallback::class)
            ->findOneBy([
                'customerGroup' => $customerGroup,
                'website' => $website
            ]);

        $fallbackChoices = [
            PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY =>
                'oro.pricing.fallback.current_customer_group_only.label',
            PriceListCustomerGroupFallback::WEBSITE =>
                'oro.pricing.fallback.website.label',
        ];

        $fallback = $fallbackEntity
            ? $fallbackChoices[$fallbackEntity->getFallback()]
            : $fallbackChoices[PriceListCustomerGroupFallback::WEBSITE];

        $isCurrentOnly = $fallbackEntity?->getFallback() === PriceListCustomerGroupFallback::CURRENT_ACCOUNT_GROUP_ONLY;
        $link = null;
        if ($this->authorizationChecker->isGranted('VIEW', $customerGroup)) {
            $link = $this->urlGenerator->generate(
                'oro_customer_customer_group_view',
                ['id' => $customerGroup->getId()]
            );
        }

        return [
            'section_title' => $this->translator->trans('oro.customer.customergroup.entity_label'),
            'link' => $link,
            'link_title' => $customerGroup->getName(),
            'fallback' => $fallback,
            'fallback_entity_title' => $isCurrentOnly ? null : $website->getName(),
            'price_lists' => $priceLists,
            'stop' => $isCurrentOnly
        ];
    }
}
