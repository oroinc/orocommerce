<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Fixtures\Entity\PageEntity;

class LoadItemData extends AbstractFixture
{
    /**
     * @var string
     */
    const REFERENCE_GOOD_PRODUCT = 'goodProduct';

    /**
     * @var string
     */
    const REFERENCE_BETTER_PRODUCT = 'betterProduct';

    /**
     * @var string
     */
    const REFERENCE_OTHER_GOOD_PRODUCT = 'otherGood';

    /**
     * @var string
     */
    const REFERENCE_OTHER_BETTER_PRODUCT = 'otherBetter';

    /**
     * @var string
     */
    const REFERENCE_GREAT_PRODUCT = 'greatProduct';

    /**
     * @var array
     */
    private static $itemsData = [
        self::REFERENCE_GOOD_PRODUCT => [
            'entity' => Product::class,
            'alias' => 'orob2b_product_1',
            'recordId' => 1,
            'title' => 'Good product',
            'datetimeFields' => [
                [
                    'field' => 'created',
                    'value' => 'now'
                ]
            ]
        ],
        self::REFERENCE_BETTER_PRODUCT => [
            'entity' => Product::class,
            'alias' => 'orob2b_product_1',
            'recordId' => 2,
            'title' => 'Better product',
            'textFields' => [
                [
                    'field' => 'long_description',
                    'value' => 'Long description'
                ]
            ]
        ],
        self::REFERENCE_OTHER_GOOD_PRODUCT => [
            'entity' => Product::class,
            'alias' => 'orob2b_product_2',
            'recordId' => 1,
            'title' => 'Good product on other website',
            'textFields' => [
                [
                    'field' => 'short_description',
                    'value' => 'Short description'
                ]
            ]
        ],
        self::REFERENCE_OTHER_BETTER_PRODUCT => [
            'entity' => Product::class,
            'alias' => 'orob2b_product_2',
            'recordId' => 2,
            'title' => 'Better product on other website',
            'decimalFields' => [
                [
                    'field' => 'price',
                    'value' => '100'
                ]
            ]
        ],
        self::REFERENCE_GREAT_PRODUCT => [
            'entity' => PageEntity::class,
            'alias' => 'orob2b_product_3',
            'recordId' => 11,
            'title' => 'Lottery ticket',
            'integerFields' => [
                [
                    'field' => 'lucky_number',
                    'value' => 777
                ]
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$itemsData as $reference => $itemData) {
            $item = new Item;
            $item
                ->setTitle($itemData['title'])
                ->setAlias($itemData['alias'])
                ->setEntity($itemData['entity'])
                ->setRecordId($itemData['recordId']);

            $manager->persist($item);

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

            $this->addReference($reference, $item);
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

            if ($fieldObject instanceof IndexDatetime) {
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
