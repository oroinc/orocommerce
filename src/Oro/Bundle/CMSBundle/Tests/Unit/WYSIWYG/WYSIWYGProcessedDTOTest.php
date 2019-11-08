<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\WYSIWYG;

use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedDTO;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;

class WYSIWYGProcessedDTOTest extends \PHPUnit\Framework\TestCase
{
    public function testGetProcessedEntity(): void
    {
        /** @var WYSIWYGProcessedEntityDTO $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);

        $this->assertSame($entityDTO, $processedDTO->getProcessedEntity());
        $this->assertTrue($processedDTO->isSelfOwner());
    }

    public function testOwnerEntity(): void
    {
        /** @var WYSIWYGProcessedEntityDTO $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);

        /** @var WYSIWYGProcessedEntityDTO $ownerEntityDTO */
        $ownerEntityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO, $ownerEntityDTO);

        $this->assertSame($entityDTO, $processedDTO->getProcessedEntity());
        $this->assertSame($ownerEntityDTO, $processedDTO->getOwnerEntity());
        $this->assertFalse($processedDTO->isSelfOwner());
    }

    public function testOwnerEntitySameProcessed(): void
    {
        /** @var WYSIWYGProcessedEntityDTO $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);

        $this->assertSame($entityDTO, $processedDTO->getOwnerEntity());
        $this->assertTrue($processedDTO->isSelfOwner());
    }

    public function testRequireOwnerEntityClass(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn('TestClassName');

        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $this->assertSame('TestClassName', $processedDTO->requireOwnerEntityClass());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Owner entity must have class name
     */
    public function testRequireOwnerEntityClassException(): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->once())
            ->method('getName')
            ->willReturn(false);

        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getMetadata')
            ->willReturn($metadata);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $processedDTO->requireOwnerEntityClass();
    }

    public function testRequireOwnerEntityId(): void
    {
        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getEntityId')
            ->willReturn(42);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $this->assertSame(42, $processedDTO->requireOwnerEntityId());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Owner entity must have identifier
     */
    public function testRequireOwnerEntityIdException(): void
    {
        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getEntityId')
            ->willReturn(null);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $processedDTO->requireOwnerEntityId();
    }

    public function testRequireOwnerEntityFieldName(): void
    {
        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getFieldName')
            ->willReturn('field_name');

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $this->assertSame('field_name', $processedDTO->requireOwnerEntityFieldName());
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Owner entity must have field name
     */
    public function testRequireOwnerEntityFieldNameException(): void
    {
        /** @var WYSIWYGProcessedEntityDTO|\PHPUnit\Framework\MockObject\MockObject $entityDTO */
        $entityDTO = $this->createMock(WYSIWYGProcessedEntityDTO::class);
        $entityDTO->expects($this->once())
            ->method('getFieldName')
            ->willReturn(null);

        $processedDTO = new WYSIWYGProcessedDTO($entityDTO);
        $processedDTO->requireOwnerEntityFieldName();
    }
}
