<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\SearchBundle\Entity\AbstractItem;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
 * Stores items in the index
 *
 * @ORM\Table(
 *    name="oro_website_search_item",
 *    uniqueConstraints={
 *      @ORM\UniqueConstraint(name="oro_website_search_item_uidx", columns={"entity", "record_id", "alias"})
 *    },
 *    indexes={
 *      @ORM\Index(name="oro_website_search_item_idxa", columns={"alias"}),
 *      @ORM\Index(name="oro_website_search_item_idxe", columns={"entity"})
 *    }
 * )
 * @ORM\HasLifecycleCallbacks
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebsiteSearchBundle\Entity\Repository\ItemRepository")
 */
class Item extends AbstractItem
{
    /**
     * Save index item data. Needed to use classes from the proper namespace.
     *
     * @param array $objectData
     *
     * @return Item
     */
    public function saveItemData($objectData)
    {
        $this->saveData($objectData, $this->textFields, new IndexText(), SearchQuery::TYPE_TEXT);
        $this->saveData($objectData, $this->integerFields, new IndexInteger(), SearchQuery::TYPE_INTEGER);
        $this->saveData($objectData, $this->datetimeFields, new IndexDatetime(), SearchQuery::TYPE_DATETIME);
        $this->saveData($objectData, $this->decimalFields, new IndexDecimal(), SearchQuery::TYPE_DECIMAL);

        return $this;
    }

    /**
     * @return array
     */
    public function getAllFields()
    {
        return [
            SearchQuery::TYPE_TEXT => $this->textFields,
            SearchQuery::TYPE_INTEGER => $this->integerFields,
            SearchQuery::TYPE_DATETIME => $this->datetimeFields,
            SearchQuery::TYPE_DECIMAL => $this->decimalFields,
        ];
    }
}
