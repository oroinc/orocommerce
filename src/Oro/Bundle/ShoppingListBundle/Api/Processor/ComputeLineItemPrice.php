<?php

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Config\EntityDefinitionFieldConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaRequestHandler;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes values for "currency" and "value" fields for a shopping list item.
 */
class ComputeLineItemPrice implements ProcessorInterface
{
    private MatchingPriceProvider $matchingPriceProvider;
    private ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler;
    private UserCurrencyManager $userCurrencyManager;

    public function __construct(
        MatchingPriceProvider $matchingPriceProvider,
        ProductPriceScopeCriteriaRequestHandler $scopeCriteriaRequestHandler,
        UserCurrencyManager $userCurrencyManager
    ) {
        $this->matchingPriceProvider = $matchingPriceProvider;
        $this->scopeCriteriaRequestHandler = $scopeCriteriaRequestHandler;
        $this->userCurrencyManager = $userCurrencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        $valueFieldName = 'value';
        $currencyFieldName = 'currency';
        if (\array_key_exists($valueFieldName, $data) || \array_key_exists($currencyFieldName, $data)) {
            // the computing values are already set
            return;
        }

        $config = $context->getConfig();
        $valueField = $config->getField($valueFieldName);
        $currencyField = $config->getField($currencyFieldName);
        if (null === $valueField && null === $currencyField) {
            // only identifier field was requested
            return;
        }
        if ($valueField->isExcluded() && $currencyField->isExcluded()) {
            // none of computing fields was requested
            return;
        }

        $data = $this->computeFields(
            $data,
            $config,
            $currencyFieldName,
            $currencyField,
            $valueFieldName,
            $valueField
        );
        $context->setData($data);
    }

    private function computeFields(
        array $data,
        EntityDefinitionConfig $config,
        string $currencyFieldName,
        EntityDefinitionFieldConfig $currencyField,
        string $valueFieldName,
        EntityDefinitionFieldConfig $valueField
    ): array {
        $productFieldName = $config->findFieldNameByPropertyPath('product');
        $quantityFieldName = $config->findFieldNameByPropertyPath('quantity');
        $unitFieldName = $config->findFieldNameByPropertyPath('unit');
        $productIdFieldName = $this->getAssociationIdFieldName($config, $productFieldName);
        $unitCodeFieldName = $this->getAssociationIdFieldName($config, $unitFieldName);

        [$value, $currency] = $this->getPrice(
            $data[$productFieldName][$productIdFieldName],
            $data[$quantityFieldName],
            $data[$unitFieldName][$unitCodeFieldName]
        );

        if (!$valueField->isExcluded()) {
            if (null !== $value) {
                $value = (string)$value;
            }
            $data[$valueFieldName] = $value;
        }
        if (!$currencyField->isExcluded()) {
            $data[$currencyFieldName] = $currency;
        }

        return $data;
    }

    private function getAssociationIdFieldName(EntityDefinitionConfig $config, string $associationName): string
    {
        $ids = $config->getField($associationName)->getTargetEntity()->getIdentifierFieldNames();

        return $ids[0];
    }

    /**
     * @param int    $productId
     * @param float  $quantity
     * @param string $unitCode
     *
     * @return array [value, currency]
     */
    private function getPrice(int $productId, float $quantity, string $unitCode): array
    {
        $prices = $this->matchingPriceProvider->getMatchingPrices(
            [
                [
                    'product'  => $productId,
                    'qty'      => $quantity,
                    'unit'     => $unitCode,
                    'currency' => $this->userCurrencyManager->getUserCurrency()
                ]
            ],
            $this->scopeCriteriaRequestHandler->getPriceScopeCriteria()
        );

        $value = null;
        $currency = null;
        if ($prices) {
            $price = reset($prices);
            $value = $price['value'];
            $currency = $price['currency'];
        }

        return [$value, $currency];
    }
}
