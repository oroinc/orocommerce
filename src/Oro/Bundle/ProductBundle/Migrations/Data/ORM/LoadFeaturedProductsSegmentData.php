<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Migrations\Data\ORM\LoadSegmentTypes;

class LoadFeaturedProductsSegmentData extends AbstractFixture implements
    DependentFixtureInterface
{
    const SEGMENT_RECORDS_LIMIT = 10;

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentTypes::class,
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $segment = new Segment();
        $segment->setName('Featured Products');
        $segment->setEntity(Product::class);
        $segment->setType($this->getSegmentType($manager, SegmentType::TYPE_DYNAMIC));
        $segment->setRecordsLimit(self::SEGMENT_RECORDS_LIMIT);

        $organization = $this->getOrganization($manager);
        $segment->setOrganization($organization);
        $segment->setOwner($organization->getBusinessUnits()->first());

        $segment->setDefinition(json_encode($this->getDefinition()));

        $manager->persist($segment);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
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
    private function getDefinition()
    {
        return [
            'columns' => [
                [
                    'name'    => 'id',
                    'label'   => 'Id',
                    'sorting' => '',
                    'func'    => null,
                ],
                [
                    'name'    => 'updatedAt',
                    'label'   => 'Updated At',
                    'sorting' => 'DESC',
                    'func'    => null,
                ],
            ],
            'filters' => [
                [
                    'columnName' => 'featured',
                    'criterion' => [
                        'filter' => 'boolean',
                        'data' => [
                            'value' => 1
                        ]
                    ]
                ]
            ]
        ];
    }
}
