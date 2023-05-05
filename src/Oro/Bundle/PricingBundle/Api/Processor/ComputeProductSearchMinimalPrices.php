<?php

namespace Oro\Bundle\PricingBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Request\DataType;
use Oro\Bundle\ApiBundle\Request\ValueTransformer;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "minimalPrices" field for ProductSearch entity.
 */
class ComputeProductSearchMinimalPrices implements ProcessorInterface
{
    public const MINIMAL_PRICES_FIELD = 'minimalPrices';

    private ValueTransformer $valueTransformer;
    private UserCurrencyManager $currencyManager;

    public function __construct(ValueTransformer $valueTransformer, UserCurrencyManager $currencyManager)
    {
        $this->valueTransformer = $valueTransformer;
        $this->currencyManager = $currencyManager;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if (!$context->isFieldRequested(self::MINIMAL_PRICES_FIELD, $data)) {
            return;
        }

        $minimalPrices = [];
        $units = $data['text_product_units'];
        if ($units) {
            $normalizationContext = $context->getNormalizationContext();
            $currency = $this->currencyManager->getUserCurrency();
            foreach ($units as $unitName => $precision) {
                $priceFieldName = $this->getPriceFieldNameForProductUnit($data, $unitName);
                if (!$priceFieldName) {
                    continue;
                }
                $price = $data[$priceFieldName];
                if (null === $price) {
                    continue;
                }

                $minimalPrices[] = [
                    'price'      => $this->valueTransformer->transformValue(
                        $price,
                        DataType::MONEY,
                        $normalizationContext
                    ),
                    'currencyId' => $currency,
                    'unit'       => $unitName
                ];
            }
        }

        $data[self::MINIMAL_PRICES_FIELD] = $minimalPrices;

        $context->setData($data);
    }

    private function getPriceFieldNameForProductUnit(array $data, string $unitName): ?string
    {
        $suffix = '_' . $unitName;
        $suffixOffset = -\strlen($suffix);
        foreach ($data as $name => $val) {
            if (str_starts_with($name, 'decimal_minimal_price_') && substr($name, $suffixOffset) === $suffix) {
                return $name;
            }
        }

        return null;
    }
}
