<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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

    /**
     * @var AbstractPriceListTreeHandler
     */
    private $priceListTreeHandler;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @var null|string
     */
    private $value;

    public function __construct(
        AbstractPriceListTreeHandler $priceListTreeHandler,
        TokenStorageInterface $tokenStorage,
        ConfigManager $configManager
    ) {
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->tokenStorage = $tokenStorage;
        $this->configManager = $configManager;
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

        $accuracy = $this->configManager->get('oro_pricing.price_indexation_accuracy');
        $customer = null;
        if ($accuracy !== 'website') {
            $token = $this->tokenStorage->getToken();
            if ($token && $token->getUser() instanceof CustomerUser) {
                /** @var Customer $baseCustomer */
                $baseCustomer = $token->getUser()->getCustomer();
                if ($baseCustomer && $accuracy === 'customer_group') {
                    $customer = new Customer();
                    $customer->setGroup($baseCustomer->getGroup());
                } else {
                    $customer = $baseCustomer;
                }
            }
        }

        $priceList = $this->priceListTreeHandler->getPriceList($customer);

        $this->value = $priceList ? (string)$priceList->getId() : '';

        return $this->value;
    }
}
