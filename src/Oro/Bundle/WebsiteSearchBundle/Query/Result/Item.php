<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Result;

use Oro\Bundle\SearchBundle\Query\Result\Item as BaseItem;
use Symfony\Component\PropertyAccess\PropertyAccess;

class Item extends BaseItem
{
    /**
     * @param string|null $entityName
     * @param string|null $recordId
     * @param string|null $recordTitle
     * @param string|null $recordUrl
     * @param array $selectedData
     * @param array $entityConfig
     */
    public function __construct(
        $entityName = null,
        $recordId = null,
        $recordTitle = null,
        $recordUrl = null,
        array $selectedData = [],
        array $entityConfig = []
    ) {
        $this->entityName = $entityName;
        $this->recordId = empty($recordId) ? 0 : $recordId;
        $this->recordTitle = $recordTitle;
        $this->recordUrl = $recordUrl;
        $this->selectedData = $selectedData;
        $this->entityConfig = $entityConfig;

        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * {@inheritdoc}
     */
    public function getEntity()
    {
        throw new \BadMethodCallException('Use getEntityName and getRecordId to load data');
    }
}
