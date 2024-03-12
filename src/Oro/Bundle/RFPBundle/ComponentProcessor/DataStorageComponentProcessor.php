<?php

namespace Oro\Bundle\RFPBundle\ComponentProcessor;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Model\Mapping\ProductMapperInterface;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to quick order process for RFQ and save the handling result into {@see ProductDataStorage}.
 */
class DataStorageComponentProcessor extends DataStorageAwareComponentProcessor
{
    private ProductRFPAvailabilityProvider $productAvailabilityProvider;
    private FeatureChecker $featureChecker;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ProductDataStorage $storage,
        ProductMapperInterface $productMapper,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        UrlGeneratorInterface $urlGenerator,
        ProductRFPAvailabilityProvider $productAvailabilityProvider,
        FeatureChecker $featureChecker
    ) {
        parent::__construct(
            $storage,
            $productMapper,
            $authorizationChecker,
            $tokenAccessor,
            $requestStack,
            $translator,
            $urlGenerator
        );
        $this->productAvailabilityProvider = $productAvailabilityProvider;
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $data, Request $request): ?Response
    {
        $initialStoredData = $this->storage->get();
        $result = parent::process($data, $request);

        $productIds = [];
        $storedData = $this->storage->get();
        foreach ($storedData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $item) {
            $productIds[] = $item[ProductDataStorage::PRODUCT_ID_KEY];
        }

        $hasProductsAllowedForRFP = $this->productAvailabilityProvider->hasProductsAllowedForRFP($productIds);
        if (!$hasProductsAllowedForRFP) {
            $this->storage->set($initialStoredData);
            $this->addFlashMessage(
                'warning',
                $this->translator->trans('oro.frontend.rfp.data_storage.no_products_be_added_to_rfq')
            );
            $result = null;
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function isAllowed(): bool
    {
        return parent::isAllowed() || $this->isAllowedForGuest();
    }

    public function isAllowedForGuest(): bool
    {
        return $this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && $this->featureChecker->isFeatureEnabled('guest_rfp');
    }
}
