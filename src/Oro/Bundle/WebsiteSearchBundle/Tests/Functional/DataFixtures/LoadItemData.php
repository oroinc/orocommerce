<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Entity\ItemFieldInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;

class LoadItemData extends AbstractFixture implements ContainerAwareInterface, DependentFixtureInterface
{
    use ContainerAwareTrait;

    /**
     * @var string
     */
    const REFERENCE_GOOD_PRODUCT = 'goodProduct';

    /**
     * @var string
     */
    const REFERENCE_BETTER_PRODUCT = 'betterProduct';

    /**
     * @var array
     */
    private static $itemsData = [
        self::REFERENCE_GOOD_PRODUCT => [
            'entity' => TestProduct::class,
            'alias' => 'oro_product_website_',
            'recordId' => LoadProductsToIndex::REFERENCE_PRODUCT1,
            'title' => 'Good product',
            'datetimeFields' => [
                [
                    'field' => 'created',
                    'value' => 'now',
                ],
            ],
            'textFields' => [
                [
                    'field' => 'long_description',
                    'value' => 'Long description',
                ],
            ],
        ],
        self::REFERENCE_BETTER_PRODUCT => [
            'entity' => TestProduct::class,
            'alias' => 'oro_product_website_',
            'recordId' => LoadProductsToIndex::REFERENCE_PRODUCT2,
            'title' => 'Better product',
            'decimalFields' => [
                [
                    'field' => 'price',
                    'value' => '100',
                ],
            ],
            'integerFields' => [
                [
                    'field' => 'lucky_number',
                    'value' => 777,
                ],
            ],
        ],
    ];

    /**
     * @var ReferenceRepository
     */
    private static $searchReferenceRepository;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductsToIndex::class,
            LoadOtherWebsite::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $websiteIds = $manager->getRepository('OroWebsiteBundle:Website')->getWebsiteIdentifiers();

        $manager = $this->container->get('oro_entity.doctrine_helper')->getEntityManager(Item::class);
        self::$searchReferenceRepository = new ReferenceRepository($manager);

        foreach ($websiteIds as $websiteId) {
            foreach (self::$itemsData as $reference => $itemData) {
                $product = $this->getReference($itemData['recordId']);

                $item = new Item;
                $item
                    ->setTitle($itemData['title'])
                    ->setAlias($itemData['alias'] . $websiteId)
                    ->setEntity($itemData['entity'])
                    ->setRecordId($product->getId());

                $manager->persist($item);

                if (isset($itemData['integerFields'])) {
                    $this->populateFields(
                        $item,
                        $item->getIntegerFields(),
                        new IndexInteger,
                        $itemData['integerFields']
                    );
                }

                if (isset($itemData['decimalFields'])) {
                    $this->populateFields(
                        $item,
                        $item->getDecimalFields(),
                        new IndexDecimal,
                        $itemData['decimalFields']
                    );
                }

                if (isset($itemData['datetimeFields'])) {
                    $datetimeFieldsData = $itemData['datetimeFields'];
                    $this->populateFields($item, $item->getDatetimeFields(), new IndexDatetime, $datetimeFieldsData);
                }

                if (isset($itemData['textFields'])) {
                    $this->populateFields($item, $item->getTextFields(), new IndexText, $itemData['textFields']);
                }

                self::getSearchReferenceRepository()->addReference(
                    self::getReferenceName($reference, $websiteId),
                    $item
                );
            }
        }

        $manager->flush();
    }

    /**
     * @param string $referenceName
     * @param int $websiteId
     * @return string
     */
    public static function getReferenceName($referenceName, $websiteId)
    {
        return $referenceName . '_website_' . $websiteId;
    }

    /**
     * @param Item $item
     * @param Collection $collection
     * @param ItemFieldInterface $fieldObject
     * @param array $fieldsData
     */
    private function populateFields(
        Item $item,
        Collection $collection,
        ItemFieldInterface $fieldObject,
        array $fieldsData
    ) {
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

            $collection->add($field);
        }
    }

    /**
     * @return ReferenceRepository
     * @throw \LogicException
     */
    public static function getSearchReferenceRepository()
    {
        if (null === self::$searchReferenceRepository) {
            throw new \LogicException('The reference repository is not set. Have you loaded fixtures?');
        }

        return self::$searchReferenceRepository;
    }
}
