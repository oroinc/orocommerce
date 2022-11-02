<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\Collection;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\ReferenceRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SearchBundle\Entity\ItemFieldInterface;
use Oro\Bundle\TestFrameworkBundle\Entity\TestEmployee;
use Oro\Bundle\TestFrameworkBundle\Entity\TestProduct;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

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
     * @var string
     */
    const REFERENCE_EMPLOYEE1 = 'employee1';

    /**
     * @var string
     */
    const REFERENCE_EMPLOYEE2 = 'employee2';

    /**
     * @var array
     */
    private static $itemsData = [
        self::REFERENCE_GOOD_PRODUCT => [
            'entity' => TestProduct::class,
            'alias' => 'oro_product_',
            'recordId' => LoadProductsToIndex::REFERENCE_PRODUCT1,
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
            'integerFields' => [
                [
                    'field' => 'for_count',
                    'value' => 100,
                ],
            ],
        ],
        self::REFERENCE_BETTER_PRODUCT => [
            'entity' => TestProduct::class,
            'alias' => 'oro_product_',
            'recordId' => LoadProductsToIndex::REFERENCE_PRODUCT2,
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
                [
                    'field' => 'for_count',
                    'value' => 200,
                ],
            ],
        ],
        self::REFERENCE_EMPLOYEE1 => [
            'entity' => TestEmployee::class,
            'alias' => 'oro_employee_',
            'recordId' => LoadEmployeesToIndex::REFERENCE_PERSON1,
            'textFields' => [
                [
                    'field' => 'name',
                    'value' => 'Steve Gates',
                ],
            ],
        ],
        self::REFERENCE_EMPLOYEE2 => [
            'entity' => TestEmployee::class,
            'alias' => 'oro_employee_',
            'recordId' => LoadEmployeesToIndex::REFERENCE_PERSON2,
            'textFields' => [
                [
                    'field' => 'name',
                    'value' => 'Bill Wozniak',
                ],
            ],
        ]
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
            LoadEmployeesToIndex::class,
            LoadOtherWebsite::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $websiteIds = $manager->getRepository(Website::class)->getWebsiteIdentifiers();

        $manager = $this->container->get('oro_entity.doctrine_helper')->getEntityManager(Item::class);
        self::$searchReferenceRepository = new ReferenceRepository($manager);

        foreach ($websiteIds as $websiteId) {
            foreach (self::$itemsData as $reference => $itemData) {
                $entity = $this->getReference($itemData['recordId']);

                $item = new Item;
                $item
                    ->setAlias($itemData['alias'] . $websiteId)
                    ->setEntity($itemData['entity'])
                    ->setRecordId($entity->getId());

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
