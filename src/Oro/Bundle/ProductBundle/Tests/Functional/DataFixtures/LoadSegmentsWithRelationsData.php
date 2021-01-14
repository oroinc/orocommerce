<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\TestFrameworkBundle\Entity\WorkflowAwareEntity;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadSegmentsWithRelationsData extends AbstractFixture
{
    const FIRST_SEGMENT = 'firstSegment';
    const SECOND_SEGMENT = 'secondSegment';
    const THIRD_SEGMENT = 'thirdSegment';
    const NO_RELATIONS_SEGMENT = 'noRelationsSegment';
    const WITH_CRITERIA_SEGMENT = 'withCriteriaSegment';

    private $definitions = [
        'withoutRelations' => [
            'filters' => [
                ['columnName' => 'column'],
            ]
        ],
        'withRelations' => [
            'filters' => [
                ['columnName' => 'column+SomeClass::id'],
            ]
        ],
        'withCriteria' => [
            'filters' => [
                ['columnName' => 'column+SomeClass::id', 'criteria' => 'condition-activity'],
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ([self::FIRST_SEGMENT, self::SECOND_SEGMENT, self::THIRD_SEGMENT] as $segmentName) {
            $segment = $this->createSegment($manager, $segmentName, 'withRelations');
            $this->setReference($segmentName, $segment);
            $manager->persist($segment);
        }

        $segment = $this->createSegment($manager, self::NO_RELATIONS_SEGMENT, 'withoutRelations');
        $this->setReference(self::NO_RELATIONS_SEGMENT, $segment);
        $manager->persist($segment);

        $segment = $this->createSegment($manager, self::WITH_CRITERIA_SEGMENT, 'withCriteria');
        $this->setReference(self::WITH_CRITERIA_SEGMENT, $segment);
        $manager->persist($segment);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $name
     * @param string $segmentDefinition
     * @return Segment
     */
    private function createSegment(ObjectManager $manager, $name, $segmentDefinition)
    {
        $segment = new Segment();
        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_DYNAMIC);

        $segment
            ->setName($name)
            ->setType($segmentType)
            ->setEntity(WorkflowAwareEntity::class);

        $segment->setDefinition(json_encode($this->definitions[$segmentDefinition]));

        return $segment;
    }
}
