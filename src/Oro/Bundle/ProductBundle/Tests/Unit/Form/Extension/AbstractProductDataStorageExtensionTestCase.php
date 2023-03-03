<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension\Stub\ProductDataStorageExtensionStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\TestFrameworkBundle\Test\Logger\LoggerAwareTraitTestTrait;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractProductDataStorageExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    use LoggerAwareTraitTestTrait;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductDataStorage */
    protected $storage;

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    protected $request;

    /** @var \PHPUnit\Framework\MockObject\MockObject|AclHelper */
    protected $aclHelper;

    /** @var ProductDataStorageExtensionStub */
    protected $extension;

    /** @var string */
    protected $productClass = 'stdClass';

    /** @var string */
    protected $dataClass = \stdClass::class;

    /** @var object */
    protected $entity;

    protected function setUp(): void
    {
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->request = $this->createMock(Request::class);
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->request = $this->createMock(Request::class);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        $this->extension = new ProductDataStorageExtensionStub(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->aclHelper,
            $this->productClass
        );

        $this->setUpLoggerMock($this->extension);

        $this->extension->setDataClass($this->dataClass);

        $this->entity = new \stdClass();
    }

    public function testBuildFormNoRequestParameter()
    {
        $this->assertRequestGetCalled(false);

        $this->storage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormExistingEntity()
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $this->assertRequestGetCalled();

        $this->storage->expects($this->any())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(true), []);
    }

    public function testBuildFormNoData()
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->assertRequestGetCalled();
        $this->assertStorageCalled([], false);

        $this->extension->buildForm($this->getFormBuilder(true), []);
    }

    public function testBuildFormWithWrongPropertiesInData()
    {
        if ($this->entity instanceof \stdClass) {
            $this->entity->product = null;
            $this->entity->anotherProperty = null;
        }

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['product' => 1, 'notExistsProperty' => 'some_string'],
        ];

        $this->assertMetadataCalled(['product' => ['targetClass' => \Oro\Bundle\ProductBundle\Entity\Product::class]]);
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertLoggerNoticeMethodCalled();

        $this->extension->buildForm($this->getFormBuilder(true), []);
    }

    protected function getProductEntity(string $sku, ProductUnit $productUnit = null): Product
    {
        $product = new Product();
        $product->setSku($sku);

        if ($productUnit) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision->setUnit($productUnit);

            $product->addUnitPrecision($productUnitPrecision);
        }

        return $product;
    }

    protected function assertRequestGetCalled(bool $result = true): void
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn($result);
    }

    protected function assertStorageCalled(array $data, bool $expectsRemove = true): void
    {
        $this->storage->expects($this->any())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects($expectsRemove ? $this->once() : $this->never())
            ->method('remove');
    }

    protected function assertProductRepositoryCalled(Product $product): void
    {
        $qb = $this->createMock(QueryBuilder::class);
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())
            ->method('getOneOrNullResult')
            ->willReturn($product);

        $repo = $this->createMock(ProductRepository::class);
        $repo->expects($this->once())
            ->method('getBySkuQueryBuilder')
            ->willReturn($qb);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->productClass)
            ->willReturn($repo);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);
    }

    protected function assertMetadataCalled(array $mappings = []): void
    {
        $metadata = $this->createMock(ClassMetadata::class);
        $metadata->expects($this->any())
            ->method('hasAssociation')
            ->willReturnCallback(function ($property) use ($mappings) {
                return array_key_exists($property, $mappings);
            });
        $metadata->expects($this->any())
            ->method('getAssociationTargetClass')
            ->willReturnCallback(function ($property) use ($mappings) {
                $this->assertArrayHasKey($property, $mappings);
                $this->assertArrayHasKey('targetClass', $mappings[$property]);

                return $mappings[$property]['targetClass'];
            });

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturn($metadata);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback([$this, 'getEntity']);
    }

    protected function initEntityMetadata(array $mappings = []): void
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturnCallback(function ($object) use ($mappings) {
                $class = is_object($object) ? ClassUtils::getClass($object) : ClassUtils::getRealClass($object);

                $metadata = new ClassMetadata($class);
                $metadata->setIdentifier([null]);

                if (!isset($mappings[$class])) {
                    return $metadata;
                }

                $reflectionClass = new \ReflectionClass($metadata);
                foreach ($mappings[$class] as $property => $value) {
                    $method = $reflectionClass->getProperty($property);
                    $method->setValue($metadata, $value);
                }

                return $metadata;
            });

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback([$this, 'getEntity']);
    }

    public function getEntity(string $className, int|string $id, string $primaryKey = 'id'): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $ident = $this->getPrimaryKey($className, $primaryKey);
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue($entities[$className][$id], $ident, $id);
        }

        return $entities[$className][$id];
    }

    protected function getPrimaryKey(string $className, string $default = 'id'): string
    {
        try {
            return $this->doctrineHelper->getEntityMetadata($className)->getSingleIdentifierFieldName() ?? $default;
        } catch (MappingException) {
            return $default;
        }
    }

    protected function getFormBuilder(bool $expectsAddEventListener = false): FormBuilderInterface
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        if ($expectsAddEventListener) {
            $builder->expects($this->any())
                ->method('addEventListener')
                ->with(
                    $this->isType('string'),
                    $this->logicalAnd(
                        $this->isInstanceOf(\Closure::class),
                        $this->callback(function (\Closure $closure) {
                            $event = $this->createMock(FormEvent::class);
                            $event->expects($this->any())
                                ->method('getData')
                                ->willReturn($this->entity);
                            $closure($event);

                            return true;
                        })
                    )
                );
        } else {
            $builder->expects($this->never())
                ->method('addEventListener');
        }

        return $builder;
    }
}
