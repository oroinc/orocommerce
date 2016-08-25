<?php

namespace Oro\Bundle\WebsiteSearchBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

use Oro\Bundle\SearchBundle\Entity\BaseItem;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

/**
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
 * @ORM\Entity(repositoryClass="Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository")
 */
class Item extends BaseItem
{
    const TABLE_NAME = 'oro_website_search_item';

    /**
     * @ORM\OneToMany(targetEntity="IndexText", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $textFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexInteger", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $integerFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDecimal", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $decimalFields;

    /**
     * @ORM\OneToMany(targetEntity="IndexDatetime", mappedBy="item", cascade={"all"}, orphanRemoval=true)
     */
    protected $datetimeFields;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->textFields = new ArrayCollection();
        $this->integerFields = new ArrayCollection();
        $this->decimalFields = new ArrayCollection();
        $this->datetimeFields = new ArrayCollection();
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|BaseIndexInteger[]
     */
    public function getIntegerFields()
    {
        return $this->integerFields;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|BaseIndexDecimal[]
     */
    public function getDecimalFields()
    {
        return $this->decimalFields;
    }

    /**
     * @return \Doctrine\Common\Collections\ArrayCollection|BaseIndexDatetime[]
     */
    public function getDatetimeFields()
    {
        return $this->datetimeFields;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getTextFields()
    {
        return $this->textFields;
    }

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
}
