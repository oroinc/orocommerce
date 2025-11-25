<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Collects IDs of line items from the incoming API request (on update)
 */
class SetUpdateLineItemsIdsSharedData implements ProcessorInterface
{
    public const string UPDATE_LINE_ITEMS_IDS = 'update_line_items_ids';

    #[\Override]
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeFormDataContext $context */

        $data = $context->getData();
        if (empty($data['items']) || !\is_array($data['items'])) {
            return;
        }

        $ids = $this->getItemsIds($data);

        if ($ids) {
            $context->getSharedData()->set(self::UPDATE_LINE_ITEMS_IDS, $ids);
        }
    }

    private function getItemsIds(array $data): array
    {
        $ids = [];
        foreach ($data['items'] as $item) {
            if (isset($item['id'])) {
                $ids[] = $item['id'];
            }
        }

        return $ids;
    }
}
