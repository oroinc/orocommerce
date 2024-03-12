<?php

namespace Oro\Bundle\ProductBundle\ImportExport\EventListener;

use Oro\Bundle\ProductBundle\ImportExport\Event\ProductNormalizerEvent;

/**
 * Listener normalize KitItems data in a special data format during product export.
 */
class KitItemsProductNormalizeEventListener
{
    public const FORMAT = 'id=%s,label=%s,optional=%s,products=%s,min_qty=%s,max_qty=%s,unit=%s';

    private static array $sensitiveSymbols = ['"', '\'', '\\', ',', '='];

    public function __construct(
        private string $skuSeparator
    ) {
    }

    /**
     * Examples of formatting the value for the kitItems field:
     *  id=42,label=Base Unit,optional=false,products=5TJ23|2RW93|1TB10,min_qty=1,max_qty=1,unit=set
     *  id=,label=",My, =Escaped= \"Kit\" \'Item\'",optional=true,products=8DO33,min_qty=1,max_qty=,unit=item
     */
    public function onNormalize(ProductNormalizerEvent $event): void
    {
        $data = $event->getPlainData();
        if (empty($data['kitItems'])) {
            return;
        }

        uasort($data['kitItems'], static fn ($a, $b) => $a['sortOrder'] <=> $b['sortOrder']);

        foreach ($data['kitItems'] as $key => $item) {
            $data['kitItems'][$key] = sprintf(
                self::FORMAT,
                $item['id'],
                $this->getDefaultLabel($item['labels']),
                $item['optional'] ? 'true' : 'false',
                $this->getKitItemProductSkus($item['kitItemProducts']),
                $item['minimumQuantity'],
                $item['maximumQuantity'],
                $item['productUnit']['code'] ?? '',
            );
        }

        $data['kitItems'] = implode(PHP_EOL, $data['kitItems']);
        $event->setPlainData($data);
    }

    private function getKitItemProductSkus(array $products): string
    {
        $products = array_map(static fn ($product) => $product['product']['sku'], $products);

        return implode($this->skuSeparator, $products);
    }

    private function getDefaultLabel(array $labels): string
    {
        $defaultLabel = '';
        foreach ($labels as $label) {
            if (empty($label['localization'])) {
                $defaultLabel = $label['string'] ?? '';
                break;
            }
        }

        if ($this->hasSensitiveSymbols($defaultLabel)) {
            $defaultLabel = addslashes($defaultLabel);
        }

        return sprintf('"%s"', $defaultLabel);
    }

    private function hasSensitiveSymbols(string $value): bool
    {
        return str_replace(self::$sensitiveSymbols, '', $value) !== $value;
    }
}
