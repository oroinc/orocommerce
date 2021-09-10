<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Data\ORM\AbstractCategoryFixture;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;

/**
 * @SuppressWarnings(PHPMD.NPathComplexity)
 */
class LoadCategoryWithEntityFallbackValuesData extends AbstractCategoryFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    private $data = [
        LoadCategoryData::FIRST_LEVEL => [
            'minimumQuantityToOrder' => [
                'reference' => 'category_1.minimumQuantityToOrder',
                'scalarValue' => 1,
            ],
            'maximumQuantityToOrder' => [
                'reference' => 'category_1.maximumQuantityToOrder',
                'scalarValue' => 99999,
            ],
            'highlightLowInventory' => [
                'reference' => 'category_1.highlightLowInventory',
                'scalarValue' => 0,
            ],
            'isUpcoming' => [
                'reference' => 'category_1.isUpcoming',
                'scalarValue' => 0,
            ],
        ],
        LoadCategoryData::SECOND_LEVEL2 => [
            'minimumQuantityToOrder' => [
                'reference' => 'category_1.minimumQuantityToOrder',
                'scalarValue' => 1,
            ],
            'maximumQuantityToOrder' => [
                'reference' => 'category_1.maximumQuantityToOrder',
                'scalarValue' => 10000,
            ],
            'highlightLowInventory' => [
                'reference' => 'category_1.highlightLowInventory',
                'scalarValue' => 1,
            ],
            'isUpcoming' => [
                'reference' => 'category_1.isUpcoming',
                'scalarValue' => 1,
            ],
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies(): array
    {
        return [
            LoadCategoryData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager): void
    {
        foreach ($this->data as $categoryName => $fieldsData) {
            /** @var Category $category */
            $category = $this->getReference($categoryName);
            $this->addEntityFieldFallbackValue($fieldsData, $category);
            $manager->persist($category);
        }

        $manager->flush();
    }

    private function createEntityFieldFallbackValue(array $data): EntityFieldFallbackValue
    {
        $value = new EntityFieldFallbackValue();

        if (array_key_exists('fallback', $data)) {
            $value->setFallback($data['fallback']);
        }

        if (array_key_exists('scalarValue', $data)) {
            $value->setScalarValue($data['scalarValue']);
        }

        if (array_key_exists('arrayValue', $data)) {
            $value->setArrayValue($data['arrayValue']);
        }

        $this->setReference($data['reference'], $value);

        return $value;
    }

    private function addEntityFieldFallbackValue(array $data, Category $category): void
    {
        if (!empty($data['manageInventory'])) {
            $category->setManageInventory($this->createEntityFieldFallbackValue($data['manageInventory']));
        }

        if (!empty($data['inventoryThreshold'])) {
            $category->setInventoryThreshold($this->createEntityFieldFallbackValue($data['inventoryThreshold']));
        }

        if (!empty($data['minimumQuantityToOrder'])) {
            $category->setMinimumQuantityToOrder(
                $this->createEntityFieldFallbackValue($data['minimumQuantityToOrder'])
            );
        }

        if (!empty($data['maximumQuantityToOrder'])) {
            $category->setMaximumQuantityToOrder(
                $this->createEntityFieldFallbackValue($data['maximumQuantityToOrder'])
            );
        }

        if (!empty($data['decrementQuantity'])) {
            $category->setDecrementQuantity($this->createEntityFieldFallbackValue($data['decrementQuantity']));
        }

        if (!empty($data['backOrder'])) {
            $category->setBackOrder($this->createEntityFieldFallbackValue($data['backOrder']));
        }

        if (!empty($data['highlightLowInventory'])) {
            $category->setHighlightLowInventory($this->createEntityFieldFallbackValue($data['highlightLowInventory']));
        }

        if (!empty($data['isUpcoming'])) {
            $category->setIsUpcoming($this->createEntityFieldFallbackValue($data['isUpcoming']));
        }
    }
}
