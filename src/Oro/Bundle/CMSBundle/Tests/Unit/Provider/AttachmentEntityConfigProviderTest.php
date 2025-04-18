<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Provider;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentEntityConfigProviderInterface;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\Provider\AttachmentEntityConfigProvider;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;

class AttachmentEntityConfigProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var ManagerRegistry */
    private $doctrine;

    /** @var AttachmentEntityConfigProviderInterface */
    private $innerAttachmentEntityConfigProvider;

    /** @var AttachmentEntityConfigProvider */
    private $provider;

    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->innerAttachmentEntityConfigProvider = $this->createMock(AttachmentEntityConfigProviderInterface::class);

        $this->provider = new AttachmentEntityConfigProvider(
            $this->doctrine,
            $this->innerAttachmentEntityConfigProvider
        );
    }

    public function testGetEntityConfig(): void
    {
        $this->innerAttachmentEntityConfigProvider
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with($entityClass = 'SampleClass')
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $this->assertSame($config, $this->provider->getEntityConfig($entityClass));
    }

    public function testGetFieldConfigWhenEmtpyEntityClass(): void
    {
        $this->doctrine
            ->expects($this->never())
            ->method('getManagerForClass');

        $this->assertNull($this->provider->getFieldConfig('', 'sampleFieldName'));
    }

    public function testGetFieldConfigWhenNoEntityManager(): void
    {
        $this->doctrine
            ->expects($this->once())
            ->method('getManagerForClass')
            ->with($entityClass = 'SampleClass')
            ->willReturn(null);

        $this->assertNull($this->provider->getFieldConfig($entityClass, 'sampleFieldName'));
    }

    /**
     * @dataProvider fieldConfigDataProvider
     */
    public function testGetFieldConfig(string $fieldName, string $fieldNameWithoutSuffix, string $fieldType): void
    {
        $this->doctrine
            ->expects(self::any())
            ->method('getManagerForClass')
            ->with($entityClass = 'SampleClass')
            ->willReturn($entityManager = $this->createMock(EntityManager::class));

        $entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->with($entityClass)
            ->willReturn($classMetadata = $this->createMock(ClassMetadata::class));

        $classMetadata
            ->expects(self::any())
            ->method('getTypeOfField')
            ->willReturn($fieldType);

        $this->innerAttachmentEntityConfigProvider
            ->expects(self::any())
            ->method('getFieldConfig')
            ->with($entityClass, $fieldNameWithoutSuffix)
            ->willReturn($config = $this->createMock(ConfigInterface::class));

        $this->assertSame($config, $this->provider->getFieldConfig($entityClass, $fieldName));
    }

    public function fieldConfigDataProvider(): array
    {
        return [
            'regular field' => [
                'fieldName' => 'sampleField',
                'fieldNameWithoutSuffix' => 'sampleField',
                'fieldType' => 'sampleType',
            ],
            'style field' => [
                'fieldName' => 'sampleField_style',
                'fieldNameWithoutSuffix' => 'sampleField',
                'fieldType' => WYSIWYGStyleType::TYPE,
            ],
            'properties field' => [
                'fieldName' => 'sampleField_properties',
                'fieldNameWithoutSuffix' => 'sampleField',
                'fieldType' => WYSIWYGPropertiesType::TYPE,
            ],
            'regular field with underscores' => [
                'fieldName' => 'sample_field',
                'fieldNameWithoutSuffix' => 'sample_field',
                'fieldType' => 'sample_type',
            ],
            'style field camel case' => [
                'fieldName' => 'sampleFieldStyle',
                'fieldNameWithoutSuffix' => 'sampleField',
                'fieldType' => WYSIWYGStyleType::TYPE,
            ],
            'properties field camel case' => [
                'fieldName' => 'sampleFieldProperties',
                'fieldNameWithoutSuffix' => 'sampleField',
                'fieldType' => WYSIWYGPropertiesType::TYPE,
            ],
        ];
    }
}
