<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\WYSIWYG;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\CMSBundle\WYSIWYG\WYSIWYGProcessedEntityDTO;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class WYSIWYGProcessedEntityDTOTest extends \PHPUnit\Framework\TestCase
{
    /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    /** @var PropertyAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $propertyAccessor;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManager::class);
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    public function testIsFieldChangedWithoutChangeSet(): void
    {
        $entity = new \stdClass();
        $dto = new WYSIWYGProcessedEntityDTO($this->em, $this->propertyAccessor, $entity);

        $this->assertTrue($dto->isFieldChanged());
    }

    public function testIsFieldChangedWithChangeSet(): void
    {
        $entity = new \stdClass();
        $changeSet = [
            'test' => ['old val', 'new val'],
            'serialized_data' => [
                ['test_serialized' => 'old val'],
                ['test_serialized' => 'new val'],
            ],
        ];

        $dto = new WYSIWYGProcessedEntityDTO($this->em, $this->propertyAccessor, $entity, $changeSet);

        $this->assertFalse($dto->isFieldChanged());
        $this->assertTrue($dto->withField('test')->isFieldChanged());
        $this->assertTrue($dto->withField('test_serialized')->isFieldChanged());
        $this->assertFalse($dto->withField('not_changed')->isFieldChanged());
    }

    public function testGetMetadata(): WYSIWYGProcessedEntityDTO
    {
        $metadata = $this->createMock(ClassMetadata::class);

        $this->em->expects($this->once())
            ->method('getClassMetadata')
            ->with(\stdClass::class)
            ->willReturn($metadata);

        $dto = new WYSIWYGProcessedEntityDTO($this->em, $this->propertyAccessor, new \stdClass());

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
     */
    public function testGetFieldValue(WYSIWYGProcessedEntityDTO $dto): void
    {
        $dto = $dto->withField('test_field');

        $dto->getEntity()->test_field = 'field_value';

        $this->assertSame('field_value', $dto->getFieldValue());
    }

    /**
     * @depends testGetMetadata
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
        $dto = new WYSIWYGProcessedEntityDTO($this->em, $this->propertyAccessor, $entity);

        $newDto = $dto->withField('test_field', 'test_type');

        $this->assertNotSame($dto, $newDto);

        $this->assertNull($dto->getFieldName());
        $this->assertNull($dto->getFieldType());

        $this->assertSame('test_field', $newDto->getFieldName());
        $this->assertSame('test_type', $newDto->getFieldType());
    }
}
