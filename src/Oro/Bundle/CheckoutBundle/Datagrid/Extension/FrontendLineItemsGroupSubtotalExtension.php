<?php

namespace Oro\Bundle\CheckoutBundle\Datagrid\Extension;

use Brick\Math\BigDecimal;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\Common\ResultsObject;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecordInterface;
use Oro\Bundle\DataGridBundle\Extension\AbstractExtension;
use Oro\Bundle\LocaleBundle\Formatter\NumberFormatter;

/**
 * Calculates group subtotal for datagrid when "Enable grouping of line items during checkout" option is enabled.
 */
class FrontendLineItemsGroupSubtotalExtension extends AbstractExtension
{
    public const string GROUP_SUBTOTAL = 'groupSubtotal';

    public function __construct(
        private array $supportedGrids,
        private ConfigProvider $configProvider,
        private RoundingServiceInterface $roundingService,
        private NumberFormatter $numberFormatter,
    ) {
    }

    #[\Override]
    public function isApplicable(DatagridConfiguration $config): bool
    {
        return \in_array($config->getName(), $this->supportedGrids, true) &&
            $this->configProvider->isLineItemsGroupingEnabled() &&
            parent::isApplicable($config);
    }

    #[\Override]
    public function visitResult(DatagridConfiguration $config, ResultsObject $result): void
    {
        $result->offsetAddToArrayByPath(
            '[metadata]',
            [
                self::GROUP_SUBTOTAL => $this->calculateGroupSubtotal($result->getData())
            ]
        );
    }

    /**
     * @param ResultRecordInterface[] $records
     */
    private function calculateGroupSubtotal(array $records): string
    {
        $currency = null;
        $groupSubtotal = BigDecimal::zero();

        foreach ($records as $record) {
            if (!$currency) {
                $currency = $record->getValue('currency');
            }

            $groupSubtotal = $groupSubtotal->plus($record->getValue('subtotalValue') ?? 0);
        }

        $groupSubtotal = $this->roundingService->round($groupSubtotal->toFloat());

        return $this->numberFormatter->formatCurrency($groupSubtotal, $currency);
    }
}
