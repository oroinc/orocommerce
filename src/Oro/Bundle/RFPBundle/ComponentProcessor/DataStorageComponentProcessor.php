<?php

namespace Oro\Bundle\RFPBundle\ComponentProcessor;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Search\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
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
    private ProductAvailabilityProvider $productAvailabilityProvider;
    private FeatureChecker $featureChecker;

    /**
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        ProductDataStorage $storage,
        ProductRepository $productRepository,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        RequestStack $requestStack,
        TranslatorInterface $translator,
        UrlGeneratorInterface $router,
        ProductAvailabilityProvider $productAvailabilityProvider,
        FeatureChecker $featureChecker
    ) {
        parent::__construct(
            $storage,
            $productRepository,
            $authorizationChecker,
            $tokenAccessor,
            $requestStack,
            $translator,
            $router
        );
        $this->productAvailabilityProvider = $productAvailabilityProvider;
        $this->featureChecker = $featureChecker;
    }

    /**
     * {@inheritDoc}
     */
    public function process(array $data, Request $request): ?Response
    {
        $hasProductsAllowedForRFP = $this->productAvailabilityProvider
            ->hasProductsAllowedForRFPByProductData($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY]);

        if (!$hasProductsAllowedForRFP) {
            $this->addFlashMessage(
                'warning',
                $this->translator->trans('oro.frontend.rfp.data_storage.no_products_be_added_to_rfq')
            );

            return null;
        }

        return parent::process($data, $request);
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
        return
            $this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken
            && $this->featureChecker->isFeatureEnabled('guest_rfp');
    }
}
