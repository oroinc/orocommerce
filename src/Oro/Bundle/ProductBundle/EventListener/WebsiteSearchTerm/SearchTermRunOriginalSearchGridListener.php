<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerAwareInterface;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Provider\LocalizationProviderInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultBefore;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilitySearchQueryModifier;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Emulates storefront search request with scope components: website, localization, customer, customer group.
 */
class SearchTermRunOriginalSearchGridListener implements FeatureCheckerAwareInterface
{
    use FeatureCheckerHolderTrait;

    private ?Website $originalWebsite = null;

    private ?Localization $originalLocalization = null;

    public function __construct(
        private ManagerRegistry $doctrine,
        private RequestStack $requestStack,
        private WebsiteManager $websiteManager,
        private LocalizationProviderInterface $localizationProvider,
        private FrontendHelper $frontendHelper,
        private ProductVisibilitySearchQueryModifier $productVisibilitySearchQueryModifier,
        private CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
    }

    public function onPreBuild(PreBuild $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->promoteDatagridUrlParameter($event, $request, 'website');
        $this->promoteDatagridUrlParameter($event, $request, 'localization');
        $this->promoteDatagridUrlParameter($event, $request, 'customer');
        $this->promoteDatagridUrlParameter($event, $request, 'customerGroup');

        $datagridConfiguration = $event->getConfig();
        $websiteId = $datagridConfiguration->offsetGetByPath('[options][urlParams][website]');

        /** @var Website $website */
        $website = $websiteId
            ? $this->doctrine->getRepository(Website::class)->find($websiteId)
            : $this->websiteManager->getDefaultWebsite();

        $this->originalWebsite = $this->websiteManager->getCurrentWebsite();
        $this->websiteManager->setCurrentWebsite($website);

        $localizationId = $datagridConfiguration->offsetGetByPath('[options][urlParams][localization]');
        if ($localizationId) {
            $localization = $this->doctrine->getRepository(Localization::class)->find($localizationId);
            $this->originalLocalization = $this->localizationProvider->getCurrentLocalization();
            $this->localizationProvider->setCurrentLocalization($localization);
        }

        $customerId = $datagridConfiguration->offsetGetByPath('[options][urlParams][customer]');
        /** @var Customer $customer */
        $customer = $customerId
            ? $this->doctrine->getRepository(Customer::class)->find($customerId)
            : null;

        if (!$customer) {
            $customerGroupId = $datagridConfiguration->offsetGetByPath('[options][urlParams][customerGroup]');
            $customerGroup = $customerGroupId
                ? $this->doctrine->getRepository(CustomerGroup::class)->find($customerGroupId)
                : $customer?->getGroup();

            if (!$customerGroup) {
                $customerGroup = $this->customerUserRelationsProvider->getCustomerGroup();
            }

            $customer = $this->doctrine->getRepository(Customer::class)
                ->getCustomerGroupFirstCustomer($customerGroup);
        }

        $this->productVisibilitySearchQueryModifier->setCurrentCustomer($customer);
    }

    private function promoteDatagridUrlParameter(PreBuild $event, Request $request, string $key): void
    {
        $value = (int)$request->get($key);

        $event->getConfig()->offsetSetByPath('[options][urlParams][' . $key . ']', $value);
    }

    public function onSearchResultBefore(SearchResultBefore $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();
        if (!$request) {
            return;
        }

        $this->frontendHelper->emulateFrontendRequest();
    }

    public function onSearchResultAfter(SearchResultAfter $event): void
    {
        if (!$this->isFeaturesEnabled()) {
            return;
        }

        $this->websiteManager->setCurrentWebsite($this->originalWebsite);
        $this->localizationProvider->setCurrentLocalization($this->originalLocalization);
        $this->productVisibilitySearchQueryModifier->setCurrentCustomer(null);
        $this->frontendHelper->resetRequestEmulation();
    }
}
