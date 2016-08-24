<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use BeSimple\SoapCommon\Type\KeyValue\DateTime;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class LoadItemData extends AbstractFixture
{
    private static $itemsData = [
        [
            'entity' => 'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
            'alias' => 'Product_1',
            'recordId' => 1,
            'title' => 'Good product',
        ],
        [
            'entity' => 'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
            'alias' => 'Product_1',
            'recordId' => 2,
            'title' => 'Better product',
        ],
        [
            'entity' => 'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
            'alias' => 'Product_2',
            'recordId' => 1,
            'title' => 'Good product on other website',
        ],
        [
            'entity' => 'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\ProductEntity',
            'alias' => 'Product_2',
            'recordId' => 2,
            'title' => 'Better product on other website',
        ],
        [
            'entity' => 'Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\PageEntity',
            'alias' => 'Page_WEBSITE_ID',
            'recordId' => 1,
            'title' => 'Great page',
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$itemsData as $itemData) {
            $item = new Item;
            $item
                ->setTitle($itemData['title'])
                ->setAlias($itemData['alias'])
                ->setEntity($itemData['entity'])
                ->setRecordId($itemData['recordId']);

            $manager->persist($item);

            /*
            if (isset($itemData['integerFields'])) {
                $this->populateFields($manager, $item, new IndexInteger, $itemData['integerFields']);
            }

            if (isset($itemData['decimalFields'])) {
                $this->populateFields($manager, $item, new IndexDecimal, $itemData['decimalFields']);
            }

            if (isset($itemData['datetimeFields'])) {
                $this->populateFields($manager, $item, new IndexDatetime, $itemData['datetimeFields']);
            }

            if (isset($itemData['textFields'])) {
                $this->populateFields($manager, $item, new IndexText, $itemData['textFields']);
            }
            */
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param Item $item
     * @param $fieldObject
     * @param array $fieldsData
     */
    private function populateFields(ObjectManager $manager, Item $item, $fieldObject, array $fieldsData)
    {
        foreach ($fieldsData as $fieldData) {
            $field = clone $fieldObject;

            if ($fieldData instanceof IndexDatetime) {
                $value = new \DateTime($fieldData['value']);
            } else {
                $value = $fieldData['value'];
            }

            $field
                ->setItem($item)
                ->setField($fieldData['field'])
                ->setValue($value);

            $manager->persist($field);
        }
    }
}
