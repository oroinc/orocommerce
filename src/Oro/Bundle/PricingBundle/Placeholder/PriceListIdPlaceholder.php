<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Provider\CustomerUserRelationsProvider;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureToggleableInterface;
use Oro\Bundle\PricingBundle\Model\AbstractPriceListTreeHandler;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AbstractPlaceholder;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Replace Search Placeholder PRICE_LIST_ID with current Price list id provided by PriceListTreeHandler.
 */
class PriceListIdPlaceholder extends AbstractPlaceholder implements FeatureToggleableInterface
{
    use FeatureCheckerHolderTrait;

    public const NAME = 'PRICE_LIST_ID';

    private AbstractPriceListTreeHandler $priceListTreeHandler;
    private TokenStorageInterface $tokenStorage;
    private ConfigManager $configManager;
    private CustomerUserRelationsProvider $customerUserRelationsProvider;

    /**
     * @var null|string
     */
    private $value;

    public function __construct(
        AbstractPriceListTreeHandler $priceListTreeHandler,
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager,
        CustomerUserRelationsProvider $customerUserRelationsProvider
    ) {
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
        $this->customerUserRelationsProvider = $customerUserRelationsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getPlaceholder()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getDefaultValue()
    {
        if (!$this->isFeaturesEnabled()) {
            return '';
        }

        if ($this->value !== null) {
            return $this->value;
        }

        $customer = $this->getCustomer();
        $priceList = $this->priceListTreeHandler->getPriceList($customer);

        $this->value = $priceList ? (string)$priceList->getId() : '';

        return $this->value;
    }

    private function getCustomer(): ?Customer
    {
        $accuracy = $this->configManager->get('oro_pricing.price_indexation_accuracy');
        if ($accuracy === 'website') {
            return null;
        }

        $token = $this->tokenStorage->getToken();
        if (null === $token) {
            return null;
        }

        $customer = null;
        if ($token->getUser() instanceof CustomerUser) {
            $baseCustomer = $token->getUser()->getCustomer();
            $customer = $baseCustomer && $accuracy === 'customer_group'
                ? (new Customer())->setGroup($baseCustomer->getGroup())
                : $baseCustomer;
        }

        if ($token instanceof AnonymousCustomerUserToken) {
            $customer = $this->customerUserRelationsProvider->getCustomerIncludingEmpty();
        }

        return $customer;
    }
}
