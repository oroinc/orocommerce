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

    public function testCreateFromLifecycleEventArgs(): WYSIWYGProcessedEntityDTO
    {
        $entity = new \stdClass();

        $dto = WYSIWYGProcessedEntityDTO::createFromLifecycleEventArgs(new LifecycleEventArgs($entity, $this->em));
        $this->assertEquals(
            new WYSIWYGProcessedEntityDTO($this->em, $entity),
            $dto
        );

        $this->assertSame($this->em, $dto->getEntityManager());
        $this->assertSame($entity, $dto->getEntity());

        return $dto;
    }

    /**
     * @depends testCreateFromLifecycleEventArgs
     * @param WYSIWYGProcessedEntityDTO $dto
     */
    public function testIsFieldChangedWithoutChangeSet(WYSIWYGProcessedEntityDTO $dto): void
    {
        $this->assertTrue($dto->isFieldChanged());
    }

    public function testCreateFromPreUpdateEventArgs(): WYSIWYGProcessedEntityDTO
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

        return $dto;
    }

    /**
     * @depends testCreateFromPreUpdateEventArgs
     * @param WYSIWYGProcessedEntityDTO $dto
     */
    public function testIsFieldChangedWithChangeSet(WYSIWYGProcessedEntityDTO $dto): void
    {
        $this->assertFalse($dto->isFieldChanged());
        $this->assertTrue($dto->withField('test')->isFieldChanged());
        $this->assertFalse($dto->withField('not_changed')->isFieldChanged());
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

    /**
     * @depends testGetMetadata
     * @param WYSIWYGProcessedEntityDTO $dto
     */
    public function testGetFieldValue(WYSIWYGProcessedEntityDTO $dto): void
    {
        $dto = $dto->withField('test_field');

        /** @var \PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $dto->getMetadata();
        $metadata->expects($this->once())
            ->method('getFieldValue')
            ->with($dto->getEntity(), 'test_field')
            ->willReturn('field_value');

        $this->assertSame('field_value', $dto->getFieldValue());
    }

    /**
     * @depends testGetMetadata
     * @param WYSIWYGProcessedEntityDTO $dto
     */
    public function testIsRelation(WYSIWYGProcessedEntityDTO $dto): void
    {
        $dto = $dto->withField('test_field');

        /** @var \PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $dto->getMetadata();
        $metadata->expects($this->once())
            ->method('hasAssociation')
            ->with('test_field')
            ->willReturn(true);

        $this->assertTrue($dto->isRelation());
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
}
