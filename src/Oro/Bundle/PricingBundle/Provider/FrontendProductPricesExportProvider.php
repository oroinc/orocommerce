<?php

namespace Oro\Bundle\PricingBundle\Provider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Exception\LogicException;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Entity\PriceAttributePriceList;
use Oro\Bundle\PricingBundle\Entity\PriceAttributeProductPrice;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaInterface;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;

/**
 * Provides product price data for frontend product export.
 */
class FrontendProductPricesExportProvider
{
    private ConfigManager $configManager;
    private ProductPriceProvider $priceProvider;
    private TokenAccessorInterface $tokenAccessor;
    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;
    private ManagerRegistry $managerRegistry;
    private UserCurrencyManager $currencyManager;
    private ?array $availableExportPriceAttributes = null;
    private ?array $productPriceAttributesPrices = null;

    /**
     * @param ConfigManager $configManager
     * @param ProductPriceProvider $priceProvider
     * @param TokenAccessorInterface $tokenAccessor
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     * @param ManagerRegistry $managerRegistry
     * @param UserCurrencyManager $currencyManager
     */
    public function __construct(
        ConfigManager $configManager,
        ProductPriceProvider $priceProvider,
        TokenAccessorInterface $tokenAccessor,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        ManagerRegistry $managerRegistry,
        UserCurrencyManager $currencyManager
    ) {
        $this->configManager = $configManager;
        $this->priceProvider = $priceProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->managerRegistry = $managerRegistry;
        $this->currencyManager = $currencyManager;
    }

    /**
     * @return bool
     */
    public function isPriceAttributesExportEnabled(): bool
    {
        return (bool) $this->configManager->get(Configuration::getConfigKeyByName(
            Configuration::PRODUCT_PRICES_EXPORT_ENABLED
        ));
    }

    /**
     * @return bool
     */
    public function isTierPricesExportEnabled(): bool
    {
        return (bool) $this->configManager->get(Configuration::getConfigKeyByName(
            Configuration::PRODUCT_PRICE_TIERS_ENABLED
        ));
    }

    /**
     * @return PriceAttributePriceList[]
     */
    public function getAvailableExportPriceAttributes(): array
    {
        if (null === $this->availableExportPriceAttributes) {
            $organization = $this->getCurrentOrganization();

            $this->availableExportPriceAttributes = $this->managerRegistry
                ->getRepository(PriceAttributePriceList::class)
                ->findBy([
                    'organization' => $organization,
                    'enabledInExport' => true
                ]);
        }

        return $this->availableExportPriceAttributes;
    }

    /**
     * @param Product $product
     * @param array $options Contains export options
     *  [
     *      'currentCurrency' => 'USD',
     *      'currentLocalizationId' => 2,
     *      'ids' => [1, 2],
     *      // ...
     *  ]
     * @return array
     */
    public function getProductPrices(Product $product, array $options): array
    {
        $currentCurrency = $this->getCurrentCurrency($options);

        if (!isset($options['currentCurrency'])) {
            $options['currentCurrency'] = $currentCurrency;
        }

        $priceAttributePrices = $this->getProductPriceAttributePrice($product, $options);

        $result = [];
        /** @var PriceAttributeProductPrice $attributePrice */
        foreach ($priceAttributePrices as $attributePrice) {
            $result[$attributePrice->getPriceList()->getFieldName()] = $attributePrice->getPrice()->getValue();
        }

        return $result;
    }

    /**
     * @param Product $product
     * @param array $options Contains export options
     *  [
     *      'currentCurrency' => 'USD',
     *      'currentLocalizationId' => 2,
     *      'ids' => [1, 2],
     *      // ...
     *  ]
     * @return array
     * [
     *    'product.id' => ProductPriceInterface[],
     *     ...
     * ]
     */
    public function getTierPrices(Product $product, array $options): array
    {
        $currentCurrency = $this->getCurrentCurrency($options);
        $currencies = $currentCurrency ? [$currentCurrency] : [];
        $productUnit = $product->getPrimaryUnitPrecision()->getUnit()->getCode() ? : null;

        $pricesScopeCriteria = $this->getProductPricesScopeCriteria();
        return $this->priceProvider->getPricesByScopeCriteriaAndProducts(
            $pricesScopeCriteria,
            [$product],
            $currencies,
            $productUnit
        );
    }

    /**
     * @param Product $product
     * @param array $options Data comes from export options
     *                       [currentCurrency => USD, currentLocalizationId => 2, ids => [1, 2], ...],
     * @return array
     */
    protected function getProductPriceAttributePrice(Product $product, array $options): array
    {
        if (null === $this->productPriceAttributesPrices) {
            if (!isset($options['ids'])) {
                return [];
            }

            $productIds = $options['ids'];
            $currency = $options['currentCurrency'];
            $this->productPriceAttributesPrices = $this->loadProductPriceAttributePrices($productIds, $currency);
        }

        $unit = $product->getPrimaryUnitPrecision()->getUnit()->getCode();

        return $this->productPriceAttributesPrices[$product->getId()][$unit] ?? [];
    }

    /**
     * @param int[] $productIds
     * @param string $currency
     * @return array
     */
    protected function loadProductPriceAttributePrices(array $productIds, string $currency): array
    {
        $availablePriceAttributes = $this->getAvailableExportPriceAttributes();
        $priceAttributesIds = array_map(
            static fn ($priceAttribute) => $priceAttribute->getId(),
            $availablePriceAttributes
        );

        $loadedPrices = $this->managerRegistry->getRepository(PriceAttributeProductPrice::class)
            ->findByPriceAttributeProductPriceIdsAndProductIds($priceAttributesIds, $productIds);

        $prices = [];

        /** @var PriceAttributeProductPrice $priceAttributeProductPrice */
        foreach ($loadedPrices as $priceAttributeProductPrice) {
            if ($priceAttributeProductPrice->getPrice()->getCurrency() === $currency) {
                $productId = $priceAttributeProductPrice->getProduct()->getId();
                $unitCode = $priceAttributeProductPrice->getUnit()->getCode();

                $prices[$productId][$unitCode][] = $priceAttributeProductPrice;
            }
        }

        return $prices;
    }

    /**
     * @return ProductPriceScopeCriteriaInterface
     */
    private function getProductPricesScopeCriteria(): ProductPriceScopeCriteriaInterface
    {
        $customerUser = $this->getUser();

        if (!$customerUser) {
            throw new LogicException('Customer user is not defined');
        }

        $customer = $customerUser->getCustomer();
        $website = $customerUser->getWebsite();

        return $this->priceScopeCriteriaFactory->create(
            $website,
            $customer,
        );
    }

    /**
     * @return Organization|null
     */
    private function getCurrentOrganization(): ?Organization
    {
        return $this->tokenAccessor->getOrganization();
    }

    /**
     * @return CustomerUser|null
     */
    private function getUser(): ?CustomerUser
    {
        $user = $this->tokenAccessor->getUser();

        if ($user instanceof CustomerUser) {
            return $user;
        }

        return null;
    }

    /**
     * @param array $options
     * @return string|null
     */
    private function getCurrentCurrency(array $options): ?string
    {
        $currency = null;
        if (isset($options['currentCurrency'])) {
            return $options['currentCurrency'];
        }

        $currency = $this->currencyManager->getUserCurrency();

        if (!$currency) {
            $currency = $this->currencyManager->getDefaultCurrency();
        }

        return $currency;
    }
}
