<?php

namespace Oro\Bundle\ShoppingListBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use InvalidArgumentException;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\PriceListRequestHandler;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

/**
 * Check if products have at least one price
 * Usage:
 * @products_have_at_least_one_price: items
 */
class ProductsHaveAtLeastOnePrice extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'products_have_at_least_one_price';

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @var ProductPriceProvider
     */
    protected $productPriceProvider;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var UserCurrencyManager
     */
    protected $userCurrencyManager;

    /**
     * @var PriceListRequestHandler
     */
    protected $priceListRequestHandler;

    /**
     * @param ProductPriceProvider $productPriceProvider
     * @param SecurityFacade $securityFacade
     * @param UserCurrencyManager $userCurrencyManager
     * @param PriceListRequestHandler $priceListRequestHandler
     */
    public function __construct(
        ProductPriceProvider $productPriceProvider,
        SecurityFacade $securityFacade,
        UserCurrencyManager $userCurrencyManager,
        PriceListRequestHandler $priceListRequestHandler
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->securityFacade = $securityFacade;
        $this->userCurrencyManager = $userCurrencyManager;
        $this->priceListRequestHandler = $priceListRequestHandler;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $lineItems = $this->resolveValue($context, $this->propertyPath);

        if ($lineItems instanceof ArrayCollection) {
            throw new InvalidArgumentException(
                'Property must be a valid ArrayCollection. but is '
                .get_class($lineItems)
            );
        }

        return $this->isThereAPricePresent($lineItems);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $option = reset($options);

        if (!$option instanceof PropertyPathInterface) {
            throw new InvalidArgumentException(
                'Condition option must be a PropertyPathInterface, but is '
                .get_class($option)
            );
        }

        $this->propertyPath = $option;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }

    /**
     * @param LineItem[]|ArrayCollection $lineItems
     * @return boolean
     */
    public function isThereAPricePresent($lineItems)
    {
        $productsPricesCriteria = [];

        foreach ($lineItems as $lineItem) {
            $productsPricesCriteria[] = new ProductPriceCriteria(
                $lineItem->getProduct(),
                $lineItem->getProductUnit(),
                $lineItem->getQuantity(),
                $this->userCurrencyManager->getUserCurrency()
            );
        }

        /** @var AccountUser $accountUser */
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        $prices = $this->productPriceProvider->getMatchedPrices(
            $productsPricesCriteria,
            $this->priceListRequestHandler->getPriceListByAccount()
        );

        $found = false;

        foreach ($prices as $key => $price) {
            if ($price instanceof Price) {
                $found = true;

                break;
            }
        }

        return $found;
    }
}
