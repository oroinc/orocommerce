<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\WYSIWYG;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;

class WYSIWYGProcessedEntityDTOTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp()
    {
        $this->em = $this->createMock(EntityManager::class);
    }

    public function testCreateFromLifecycleEventArgs(): void
    {
        $entity = new \stdClass();

        $dto = WYSIWYGProcessedEntityDTO::createFromLifecycleEventArgs(new LifecycleEventArgs($entity, $this->em));
        $this->assertEquals(
            new WYSIWYGProcessedEntityDTO($this->em, $entity),
            $dto
        );

        $this->assertSame($this->em, $dto->getEntityManager());
        $this->assertSame($entity, $dto->getEntity());
    }

    public function testCreateFromPreUpdateEventArgs(): void
    {
        $entity = new \stdClass();

        $changeSet = ['test' => ['old val', 'new val']];
        $dto = WYSIWYGProcessedEntityDTO::createFromLifecycleEventArgs(
            new PreUpdateEventArgs($entity, $this->em, $changeSet)
        );
        $this->assertEquals(
            new WYSIWYGProcessedEntityDTO($this->em, $entity, $changeSet),
            $dto
        );

        $this->assertSame($this->em, $dto->getEntityManager());
        $this->assertSame($entity, $dto->getEntity());
    }

    public function testGetMetadata(): WYSIWYGProcessedEntityDTO
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        $dto = new WYSIWYGProcessedEntityDTO($this->em, new \stdClass());

        $this->assertSame($metadata, $dto->getMetadata());

        // Second call must not call entity manager
        $this->assertSame($metadata, $dto->getMetadata());

        return $dto;
    }

    /**
     * @depends testGetMetadata
     */
    public function testGetEntityId(WYSIWYGProcessedEntityDTO $dto): void
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $dto->getMetadata();
        $metadata->expects($this->once())
            ->method('getIdentifierValues')
            ->with($dto->getEntity())
            ->willReturn(['id' => 42]);

        $this->assertSame(42, $dto->getEntityId());
    }

    public function testField(): void
    {
        $entity = new \stdClass();
        $dto = new WYSIWYGProcessedEntityDTO($this->em, $entity);

        $newDto = $dto->withField('test_field', 'test_type');

        $this->assertNotSame($dto, $newDto);

        $this->assertNull($dto->getFieldName());
        $this->assertNull($dto->getFieldType());

        $this->assertSame('test_field', $newDto->getFieldName());
        $this->assertSame('test_type', $newDto->getFieldType());
    }

    public function testFilterChangedFieldsFromChangeSet(): void
    {
        $entity = new \stdClass();
        $dto = new WYSIWYGProcessedEntityDTO($this->em, $entity, [
            'field_a' => [null, 'new_field_a'],
            'field_b' => ['old_field_b', 'new_field_b'],
            'field_c' => ['old_field_c', null],
        ]);

        $this->assertSame(
            [
                'field_a' => 'new_field_a',
                'field_c' => null,
            ],
            $dto->filterChangedFields(['field_a', 'field_c'])
        );

        $this->assertSame(
            [
                'field_b' => 'new_field_b',
            ],
            $dto->filterChangedFields(['field_b'])
        );
    }

    public function testFilterChangedFieldsFromEntityValue(): void
    {
        $entity = new \stdClass();
        $dto = new WYSIWYGProcessedEntityDTO($this->em, $entity);

        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->exactly(2))
            ->method('getFieldValue')
            ->withConsecutive(
                [$entity, 'field_a'],
                [$entity, 'field_b']
            )
            ->willReturnOnConsecutiveCalls('field_a_data', 'field_b_data');

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        $this->assertSame(
            [
                'field_a' => 'field_a_data',
                'field_b' => 'field_b_data',
            ],
            $dto->filterChangedFields(['field_a', 'field_b'])
        );
    }
}
