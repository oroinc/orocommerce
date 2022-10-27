<?php

namespace Oro\Bundle\PricingBundle\Placeholder;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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

    /**
     * @var CombinedPriceListTreeHandler
     */
    private $priceListTreeHandler;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    public function __construct(CombinedPriceListTreeHandler $priceListTreeHandler, TokenStorageInterface $tokenStorage)
    {
        $this->priceListTreeHandler = $priceListTreeHandler;
        $this->tokenStorage = $tokenStorage;
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

        $token = $this->tokenStorage->getToken();
        $customer = null;

        if ($token && $token->getUser() instanceof CustomerUser) {
            $customer = $token->getUser()->getCustomer();
        }

        $cpl = $this->priceListTreeHandler->getPriceList($customer);

        if (!$cpl) {
            throw new \RuntimeException('Can\'t get current cpl');
        }

        return (string) $cpl->getId();
    }
}
