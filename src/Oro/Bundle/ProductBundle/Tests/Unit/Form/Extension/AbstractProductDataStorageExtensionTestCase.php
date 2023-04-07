<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractProductDataStorageExtensionTestCase extends \PHPUnit\Framework\TestCase
{
    /** @var Request|\PHPUnit\Framework\MockObject\MockObject */
    protected $request;

    /** @var ProductDataStorage|\PHPUnit\Framework\MockObject\MockObject */
    protected $storage;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $doctrineHelper;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    protected $aclHelper;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $logger;

    /** @var AbstractProductDataStorageExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->request = $this->createMock(Request::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testBuildFormNoRequestParameter(): void
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn(false);

        $this->storage->expects($this->never())
            ->method($this->anything());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, []);
    }

    public function testBuildFormExistingEntity(): void
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getSingleEntityIdentifier')
            ->willReturn(1);

        $this->expectsGetStorageFromRequest();

        $this->storage->expects($this->any())
            ->method('get');

        $this->extension->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormNoData(): void
    {
        $this->doctrineHelper->expects($this->never())
            ->method('getSingleEntityIdentifier')
            ->willReturn(null);
        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepository');

        $this->expectsGetStorageFromRequest();

        $this->storage->expects($this->any())
            ->method('get')
            ->willReturn([]);
        $this->storage->expects($this->never())
            ->method('remove');

        $this->extension->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormWithWrongPropertiesInData(): void
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['notExistsProperty' => 'some_string']
        ];

        $this->expectsEntityMetadata([]);
        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);

        $this->logger->expects($this->once())
            ->method('notice')
            ->with(
                'No such property {property} in the entity {entity}',
                $this->callback(function (array $context) {
                    $this->assertEquals('notExistsProperty', $context['property']);
                    $this->assertEquals(get_class($this->getTargetEntity()), $context['entity']);
                    $this->assertInstanceOf(NoSuchPropertyException::class, $context['exception']);

                    return true;
                })
            );

        $this->extension->buildForm($this->getFormBuilder(), []);
    }

    protected function expectsGetStorageFromRequest(): void
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn(true);
    }

    protected function expectsGetDataFromStorage(array $data): void
    {
        $this->storage->expects($this->any())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects($this->once())
            ->method('remove');
    }

    protected function expectsGetProductFromEntityRepository(Product $product): void
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
            ->with(Product::class)
            ->willReturn($repo);

        $this->aclHelper->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);
    }

    protected function expectsEntityMetadata(array $mappings = []): void
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

    abstract protected function getTargetEntity(): object;

    protected function getRequestStack(): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects($this->any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        return $requestStack;
    }

    protected function getProduct(string $sku, ProductUnit $productUnit = null): Product
    {
        $product = new ProductStub();
        $product->setSku($sku);
        if (null !== $productUnit) {
            $productUnitPrecision = new ProductUnitPrecision();
            $productUnitPrecision->setUnit($productUnit);
            $product->addUnitPrecision($productUnitPrecision);
        }

        return $product;
    }

    protected function getProductUnit(string $code): ProductUnit
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        return $productUnit;
    }

    public function getEntity(string $className, int|string $id, string $primaryKey = 'id'): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue(
                $entities[$className][$id],
                $this->getPrimaryKey($className, $primaryKey),
                $id
            );
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

    protected function getFormBuilder(): FormBuilderInterface
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects($this->any())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                $this->logicalAnd(
                    $this->isInstanceOf(\Closure::class),
                    $this->callback(function (\Closure $closure) {
                        $event = $this->createMock(FormEvent::class);
                        $event->expects($this->any())
                            ->method('getData')
                            ->willReturn($this->getTargetEntity());
                        $closure($event);

                        return true;
                    })
                )
            );

        return $builder;
    }
}
