<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Model\CombinedPriceListTreeHandler;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Replace Search Placeholder CPL_ID with current CPL id provided by PriceListTreeHandler.
 */
class CPLIdPlaceholder extends AbstractPlaceholder implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    const NAME = 'CPL_ID';

    private CombinedPriceListTreeHandler $priceListTreeHandler;
    private TokenStorageInterface $tokenStorage;
    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    public function __construct(
        CombinedPriceListTreeHandler $priceListTreeHandler,
        TokenStorageInterface $tokenStorage,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->tokenStorage = $tokenStorage;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    #[\Override]
    public function getPlaceholder()
    {
        return self::NAME;
    }

    #[\Override]
    public function getDefaultValue()
    {
        if (!$this->isFeaturesEnabled()) {
            return '';
        }

        $token = $this->tokenStorage->getToken();
        $customer = null;

        if ($token && $token->getUser() instanceof CustomerUser) {
            $customer = $token->getUser()->getCustomer();
        } elseif ($token instanceof AnonymousCustomerUserToken) {
            $customer = $this->customerUserRelationsProvider->getCustomerIncludingEmpty();
        }

        $cpl = $this->priceListTreeHandler->getPriceList($customer);

        if (!$cpl) {
            throw new \RuntimeException('Can\'t get current cpl');
        }

        return (string) $cpl->getId();
    }
}
