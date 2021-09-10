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

class DataStorageComponentProcessor extends DataStorageAwareComponentProcessor
{
    /** @var RequestDataStorageExtension */
    protected $requestDataStorageExtension;

    /** @var FeatureChecker */
    protected $featureChecker;

    /**
     * Processor constructor.
     */
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
        $isAllowedRFP = $this->requestDataStorageExtension
            ->isAllowedRFP($data[ProductDataStorage::ENTITY_ITEMS_DATA_KEY])
        ;

        if (!$isAllowedRFP) {
            $this->session->getFlashBag()->add(
                'warning',
                $this->translator->trans('oro.frontend.rfp.data_storage.no_products_be_added_to_rfq')
            );

            return null;
        }

        return parent::process($data, $request);
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
