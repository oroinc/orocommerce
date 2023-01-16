<?php
declare(strict_types=1);

namespace Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FilterBundle\Filter\FilterUtility;
use Oro\Bundle\FilterBundle\Form\Type\Filter\BooleanFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\Demo\ORM\LoadProductDemoData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Migrations\Data\ORM\LoadSegmentTypes;
use Oro\Bundle\UserBundle\DataFixtures\UserUtilityTrait;

/**
 * Creates category-based product segments to be used for web catalog nodes with product collections.
 */
class LoadCategoryBasedSegmentsDemoData extends AbstractFixture implements DependentFixtureInterface
{
    use UserUtilityTrait;

    public const NEW_ARRIVALS_PREFIX = 'New Arrivals / ';

    public function getDependencies(): array
    {
        return [
            LoadProductDemoData::class,
            LoadCategoryDemoData::class,
            LoadProductCategoryDemoData::class,
            LoadSegmentTypes::class,
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $user = $this->getFirstUser($manager);
        $organization = $user->getOrganization();
        $businessUnit = $user->getOwner();

        $dynamicType = $manager->getRepository(SegmentType::class)->findOneBy(['name' => SegmentType::TYPE_DYNAMIC]);
        /** @noinspection PhpUnreachableStatementInspection */

        // add segment for all products
        $segment = (new Segment())
            ->setName('All Products')
            ->setEntity(Product::class)
            ->setType($dynamicType)
            ->setOrganization($organization)
            ->setOwner($businessUnit)
            ->setDefinition(\json_encode($this->getDefaultSegmentDefinition()))
        ;
        $this->addReference('product_segment.' . 'All Products', $segment);
        $manager->persist($segment);

        $categories = $manager->getRepository(Category::class)->findAll();
        foreach ($categories as $category) {
            if ($category->getOrganization()->getId() !== $organization->getId()) {
                continue;
            }
            $categoryName = $category->getDefaultTitle()->getString();
            $segmentName = static::NEW_ARRIVALS_PREFIX . $categoryName;

            $segment = $manager->getRepository(Segment::class)
                ->findOneBy(['name' => $segmentName, 'organization' => $organization]);
            if ($segment) {
                // may have already been created by LoadSegmentsForWebCatalogDemoData in older versions
            } else {
                $segment = (new Segment())
                    ->setName($segmentName)
                    ->setEntity(Product::class)
                    ->setType($dynamicType)
                    ->setOrganization($organization)
                    ->setOwner($businessUnit)
                    ->setDefinition(\json_encode($this->getSegmentDefinitionForCategory($category)))
                ;
                $manager->persist($segment);
            }

            $this->addReference('product_segment.' . $segmentName, $segment);
        }

        $manager->flush();
    }

    private function getDefaultSegmentDefinition(): array
    {
        return [
            'columns' => [
                ['name' => 'id', 'label' => 'ID', 'sorting' => null, 'func' => null],
                ['name' => 'sku', 'label' => 'SKU', 'sorting' => null, 'func' => null],
            ],
            'filters' => []
        ];
    }

    private function getSegmentDefinitionForCategory(Category $category): array
    {
        $definition = $this->getDefaultSegmentDefinition();
        $definition['filters'] = [
            [
                [
                    'columnName' => \sprintf('category+%s::id', Category::class),
                    'criterion' => [
                        'filter' => 'number',
                        'data' => [
                            'value' => $category->getId(),
                            'type' => NumberFilterType::TYPE_EQUAL,
                        ],
                    ],
                ],
                FilterUtility::CONDITION_AND,
                [
                    'columnName' => 'newArrival',
                    'criterion' => [
                        'filter' => 'boolean',
                        'data' => [
                            'value' => BooleanFilterType::TYPE_YES,
                        ],
                    ],
                ],
            ],
        ];

        return $definition;
    }
}
