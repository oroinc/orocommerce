<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\OrganizationBundle\Migrations\Data\ORM\LoadOrganizationAndBusinessUnitData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Migrations\Data\ORM\LoadSegmentTypes;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LoadNewArrivalProductsSegmentData extends AbstractFixture implements
    DependentFixtureInterface,
    ContainerAwareInterface
{
    /**
     * @internal
     */
    const NEW_ARRIVALS_SEGMENT_NAME_PARAMETER_NAME = 'oro_product.segment.new_arrival.name';

    /**
     * @var string
     */
    private $newArrivalsSegmentName;

    /**
     * {@inheritDoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->newArrivalsSegmentName = $container->getParameter(self::NEW_ARRIVALS_SEGMENT_NAME_PARAMETER_NAME);
    }

    /**
     * {@inheritDoc}
     */
    public function getDependencies()
    {
        return [
            LoadSegmentTypes::class,
            LoadOrganizationAndBusinessUnitData::class,
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function load(ObjectManager $manager)
    {
        $segmentName = $this->newArrivalsSegmentName;

        if ($this->isSegmentAlreadyExists($manager, $segmentName)) {
            return;
        }

        $segment = new Segment();
        $segment->setName($segmentName);
        $segment->setEntity(Product::class);
        $segment->setType($this->getSegmentType($manager, SegmentType::TYPE_DYNAMIC));

        $organization = $this->getOrganization($manager);
        $segment->setOrganization($organization);
        $segment->setOwner($organization->getBusinessUnits()->first());

        $segment->setDefinition(json_encode($this->getDefinition()));

        $manager->persist($segment);
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $name
     *
     * @return bool
     */
    private function isSegmentAlreadyExists(ObjectManager $manager, $name)
    {
        $segment = $manager->getRepository(Segment::class)->findOneBy(['name' => $name]);

        return (bool)$segment;
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
        return $manager->getRepository(Organization::class)->getFirst();
    }

    /**
     * @return array
     */
    private function getDefinition()
    {
        return [
            'columns' => [
                [
                    'name' => 'id',
                    'label' => 'Id',
                    'sorting' => 'DESC',
                    'func' => null,
                ],
                [
                    'name' => 'updatedAt',
                    'label' => 'Updated At',
                    'sorting' => 'DESC',
                    'func' => null,
                ],
            ],
            'filters' => [
                [
                    'columnName' => 'newArrival',
                    'criterion' => [
                        'filter' => 'boolean',
                        'data' => [
                            'value' => 1,
                        ],
                    ],
                ],
            ],
        ];
    }
}
