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
use Oro\Bundle\PricingBundle\Model\ProductPriceInterface;
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

    private ProductPriceProviderInterface $priceProvider;

    private TokenAccessorInterface $tokenAccessor;

    private ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory;

    private ManagerRegistry $managerRegistry;

    private UserCurrencyManager $currencyManager;

    private ?array $availableExportPriceAttributes = null;

    private ?array $productPriceAttributesPrices = null;

    private ?array $productPrices = null;

    public function __construct(
        ManagerRegistry $managerRegistry,
        ConfigManager $configManager,
        ProductPriceProviderInterface $priceProvider,
        TokenAccessorInterface $tokenAccessor,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory,
        UserCurrencyManager $currencyManager
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->configManager = $configManager;
        $this->priceProvider = $priceProvider;
        $this->tokenAccessor = $tokenAccessor;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
        $this->currencyManager = $currencyManager;
    }

    public function isPricesExportEnabled(): bool
    {
        return (bool)$this->configManager->get(
            Configuration::getConfigKeyByName(
                Configuration::PRODUCT_PRICES_EXPORT_ENABLED
            )
        );
    }

    public function isTierPricesExportEnabled(): bool
    {
        return (bool) $this->configManager->get(Configuration::getConfigKeyByName(
            Configuration::PRODUCT_PRICE_TIERS_ENABLED
        ));
    }

    public function getAvailableExportPriceAttributes(): array
    {
        if ($this->availableExportPriceAttributes === null) {
            $organization = $this->getCurrentOrganization();

            $this->availableExportPriceAttributes = $this->managerRegistry
                ->getRepository(PriceAttributePriceList::class)
                ->findBy(
                    [
                        'organization' => $organization,
                        'enabledInExport' => true,
                    ]
                );
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
     * @return PriceAttributeProductPrice[]
     */
    public function getProductPriceAttributesPrices(Product $product, array $options): array
    {
        if ($this->productPriceAttributesPrices === null) {
            $productIds = $options['ids'] ?? [$product->getId()];
            $currency = $this->getCurrentCurrency($options);
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
    private function loadProductPriceAttributePrices(array $productIds, string $currency): array
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
     * @param Product $product
     * @param array $options Contains export options
     *  [
     *      'currentCurrency' => 'USD',
     *      'currentLocalizationId' => 2,
     *      'ids' => [1, 2],
     *      // ...
     *  ]
     * @return ProductPriceInterface|null
     */
    public function getProductPrice(Product $product, array $options): ?ProductPriceInterface
    {
        $currentCurrency = $this->getCurrentCurrency($options);
        $this->loadProductPrices($options['ids'] ?? [$product->getId()], $currentCurrency);

        if (isset($this->productPrices[$product->getId()])) {
            /** @var ProductPriceInterface $productPrice */
            foreach ($this->productPrices[$product->getId()] as $productPrice) {
                if ($productPrice->getUnit()->getCode() === $product->getPrimaryUnitPrecision()->getUnit()->getCode()) {
                    return $productPrice;
                }
            }
        }

        return null;
    }

    private function loadProductPrices(array $productsIds, string $currency): void
    {
        if ($this->productPrices === null) {
            $pricesScopeCriteria = $this->getProductPricesScopeCriteria();
            $this->productPrices = $this->priceProvider->getPricesByScopeCriteriaAndProducts(
                $pricesScopeCriteria,
                $productsIds,
                [$currency]
            );
        }
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
        $this->loadProductPrices($options['ids'] ?? [$product->getId()], $currentCurrency);

        return $this->productPrices[$product->getId()] ?? [];
    }

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

    private function getCurrentOrganization(): ?Organization
    {
        return $this->tokenAccessor->getOrganization();
    }

    private function getUser(): ?CustomerUser
    {
        $user = $this->tokenAccessor->getUser();

        if ($user instanceof CustomerUser) {
            return $user;
        }

        return null;
    }

    private function getCurrentCurrency(array $options): string
    {
        if (isset($options['currentCurrency'])) {
            $currency = $options['currentCurrency'];
        } else {
            $currency = $this->currencyManager->getUserCurrency() ?: $this->currencyManager->getDefaultCurrency();
        }

        return (string) $currency;
    }
}
