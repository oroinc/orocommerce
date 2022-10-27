<?php

namespace Oro\Bundle\WebCatalogBundle\Migrations\Data\Demo\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Migrations\Data\Demo\ORM\LoadCategoryDemoData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Migrations\Data\ORM\LoadSegmentTypes;

class LoadSegmentsForWebCatalogDemoData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @internal
     */
    const SEGMENT_NAME_PREFIX = 'New Arrivals / ';

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryDemoData::class,
            LoadSegmentTypes::class,
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        // add segment for all products
        $segment = $this->createNewArrivalSegment(
            $this->getOrganization($manager),
            $this->getSegmentType($manager, SegmentType::TYPE_DYNAMIC),
            'All Products',
            $this->getDefaultSegmentDefinition()
        );

        $manager->persist($segment);

        $categoryNames = $this->getNewArrivalCategoryNames();
        foreach ($categoryNames as $categoryName) {
            $segment = $this->createNewArrivalSegment(
                $this->getOrganization($manager),
                $this->getSegmentType($manager, SegmentType::TYPE_DYNAMIC),
                $this->getSegmentNameFromCategoryName($categoryName),
                $this->getSegmentDefinitionForCategoryName($categoryName)
            );

            $manager->persist($segment);
        }

        $manager->flush();
    }

    /**
     * @param Organization $organization
     * @param SegmentType  $type
     * @param string       $name
     * @param array        $definition
     *
     * @return Segment
     */
    private function createNewArrivalSegment(Organization $organization, SegmentType $type, $name, array $definition)
    {
        $segment = new Segment();
        $segment->setName($name);
        $segment->setEntity(Product::class);
        $segment->setType($type);

        $segment->setOrganization($organization);
        $segment->setOwner($organization->getBusinessUnits()->first());

        $segment->setDefinition(json_encode($definition));

        return $segment;
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     *
     * @return SegmentType
     */
    private function getSegmentType(ObjectManager $manager, $name)
    {
        $repository = $manager->getRepository(SegmentType::class);

        return $repository->findOneBy(['name' => $name]);
    }

    /**
     * @param ObjectManager $manager
     *
     * @return Organization
     */
    private function getOrganization(ObjectManager $manager)
    {
        return $manager->getRepository('OroOrganizationBundle:Organization')->getFirst();
    }

    /**
     * @return array
     */
    private function getDefaultSegmentDefinition()
    {
        return [
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'id',
                    'sorting' => null,
                    'func' => null,
                ],
                [
                    'name' => 'sku',
                    'label' => 'sku',
                    'sorting' => null,
                    'func' => null,
                ],
            ],
            'filters' => []
        ];
    }

    /**
     * @param string $categoryName
     *
     * @return array
     */
    private function getSegmentDefinitionForCategoryName($categoryName)
    {
        $columnName = sprintf('category+%s::titles+%s::string', Category::class, LocalizedFallbackValue::class);

        $definition = $this->getDefaultSegmentDefinition();
        $definition['filters'] = [
            [
                [
                    'columnName' => $columnName,
                    'criterion' => [
                        'filter' => 'string',
                        'data' => [
                            'value' => $categoryName,
                            'type' => '1',
                        ],
                    ],
                ],
                'AND',
                [
                    'columnName' => 'newArrival',
                    'criterion' => [
                        'filter' => 'boolean',
                        'data' => [
                            'value' => '1',
                        ],
                    ],
                ],
            ],
        ];

        return $definition;
    }

    /**
     * @param string $categoryName
     *
     * @return string
     */
    private function getSegmentNameFromCategoryName($categoryName)
    {
        return self::SEGMENT_NAME_PREFIX . $categoryName;
    }

    /**
     * @return array
     */
    private function getNewArrivalCategoryNames()
    {
        return [
            'Lighting Products',
            'Architectural Floodlighting',
            'Medical Apparel',
            'Office Furniture',
            'Retail Supplies',
        ];
    }
}
