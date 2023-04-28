<?php

namespace Oro\Bundle\RFPBundle\ComponentProcessor;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\ProductBundle\ComponentProcessor\DataStorageAwareComponentProcessor;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Handles logic related to quick order process for RFQ and save the handling result into {@see ProductDataStorage}.
 */
class DataStorageComponentProcessor extends DataStorageAwareComponentProcessor
{
    /** @var RequestDataStorageExtension */
    protected $requestDataStorageExtension;

    /** @var FeatureChecker */
    protected $featureChecker;

    public function __construct(
        UrlGeneratorInterface $router,
        ProductDataStorage $storage,
        AuthorizationCheckerInterface $authorizationChecker,
        TokenAccessorInterface $tokenAccessor,
        Session $session,
        TranslatorInterface $translator,
        RequestDataStorageExtension $requestDataStorageExtension,
        FeatureChecker $featureChecker
    ) {
        $this->requestDataStorageExtension = $requestDataStorageExtension;
        $this->featureChecker = $featureChecker;

        parent::__construct($router, $storage, $authorizationChecker, $tokenAccessor, $session, $translator);
    }

    /**
     * {@inheritdoc}
     */
    public function process(array $data, Request $request)
    {
        $initialStoredData = $this->storage->get();
        $result = parent::process($data, $request);

        $productIds = [];
        $storedData = $this->storage->get();
        foreach ($storedData[ProductDataStorage::ENTITY_ITEMS_DATA_KEY] as $item) {
            $productIds[] = $item[ProductDataStorage::PRODUCT_ID_KEY];
        }

        $hasProductsAllowedForRFP = $this->requestDataStorageExtension->isAllowedRFPByProductsIds($productIds);
        if (!$hasProductsAllowedForRFP) {
            $this->storage->set($initialStoredData);
            $this->session->getFlashBag()->add(
                'warning',
                $this->translator->trans('oro.frontend.rfp.data_storage.no_products_be_added_to_rfq')
            );
            $result = null;
        }

        return $result;
    }

    /**
     * {@inheritdoc}
     */
    public function isAllowed()
    {
        return parent::isAllowed() || $this->isAllowedForGuest();
    }

    /**
     * @return bool
     */
    public function isAllowedForGuest()
    {
        $isAllowed = false;

        if ($this->tokenAccessor->getToken() instanceof AnonymousCustomerUserToken) {
            $isAllowed = $this->featureChecker->isFeatureEnabled('guest_rfp');
        }

        return $isAllowed;
    }
}
