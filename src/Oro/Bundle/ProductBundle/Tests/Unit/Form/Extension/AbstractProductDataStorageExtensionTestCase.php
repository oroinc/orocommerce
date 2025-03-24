<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\Mapping\RuntimeReflectionService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Form\Extension\AbstractProductDataStorageExtension;
use Oro\Bundle\ProductBundle\Storage\ProductDataStorage;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as ProductStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
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
abstract class AbstractProductDataStorageExtensionTestCase extends TestCase
{
    protected Request&MockObject $request;
    protected ProductDataStorage&MockObject $storage;
    protected ManagerRegistry&MockObject $doctrine;
    protected LoggerInterface&MockObject $logger;
    protected EntityManagerInterface&MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = $this->createMock(Request::class);
        $this->storage = $this->createMock(ProductDataStorage::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->request = $this->createMock(Request::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->doctrine->expects(self::any())
            ->method('getManagerForClass')
            ->with(get_class($this->getTargetEntity()))
            ->willReturn($this->entityManager);
    }

    abstract protected function getExtension(): AbstractProductDataStorageExtension;

    abstract protected function getTargetEntity(): object;

    public function testBuildFormNoRequestParameter(): void
    {
        $this->request->expects(self::any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn(false);

        $this->storage->expects(self::never())
            ->method(self::anything());

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::never())
            ->method('addEventListener');

        $this->getExtension()->buildForm($builder, []);
    }

    public function testBuildFormExistingEntity(): void
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['id' => 123]
        ];

        ReflectionUtil::setId($this->getTargetEntity(), 123);

        $this->expectsGetStorageFromRequest();

        $this->storage->expects(self::once())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects(self::never())
            ->method('remove');

        $this->getExtension()->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormNoData(): void
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => []
        ];

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);

        $this->getExtension()->buildForm($this->getFormBuilder(), []);
    }

    public function testBuildFormWithWrongPropertiesInData(): void
    {
        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['notExistsProperty' => 'some_string']
        ];

        $this->expectsGetStorageFromRequest();
        $this->expectsGetDataFromStorage($data);

        $this->logger->expects(self::once())
            ->method('notice')
            ->with(
                'No such property {property} in the entity {entity}',
                self::callback(function (array $context) {
                    self::assertEquals('notExistsProperty', $context['property']);
                    self::assertEquals(get_class($this->getTargetEntity()), $context['entity']);
                    self::assertInstanceOf(NoSuchPropertyException::class, $context['exception']);

                    return true;
                })
            );

        $this->getExtension()->buildForm($this->getFormBuilder(), []);
    }

    protected function expectsGetStorageFromRequest(): void
    {
        $this->request->expects(self::any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn(true);
    }

    protected function expectsGetDataFromStorage(array $data): void
    {
        $this->storage->expects(self::any())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects(self::once())
            ->method('remove');
    }

    protected function expectsFindProduct(int $productId, Product $product): void
    {
        $this->entityManager->expects(self::once())
            ->method('find')
            ->with(Product::class, $productId)
            ->willReturn($product);
    }

    protected function initEntityMetadata(array $mappings): void
    {
        $this->entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturnCallback(function ($object) use ($mappings) {
                $class = is_object($object) ? ClassUtils::getClass($object) : ClassUtils::getRealClass($object);
                $classMapping = $mappings[$class] ?? [];

                $metadata = new ClassMetadata($class);
                $identifierFieldNames = $classMapping['identifier'] ?? ['id'];
                $metadata->setIdentifier($identifierFieldNames);
                foreach ($identifierFieldNames as $fieldName) {
                    $metadata->mapField(['fieldName' => $fieldName]);
                }
                if (isset($classMapping['associationMappings'])) {
                    $metadata->associationMappings = $classMapping['associationMappings'];
                }
                $metadata->wakeupReflection(new RuntimeReflectionService());

                return $metadata;
            });

        $this->entityManager->expects(self::any())
            ->method('getReference')
            ->willReturnCallback(function (string $className, int|string $id) {
                return $this->getEntity($className, $id);
            });
    }

    protected function getRequestStack(): RequestStack
    {
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->expects(self::any())
            ->method('getCurrentRequest')
            ->willReturn($this->request);

        return $requestStack;
    }

    protected function getProduct(string $sku, ?ProductUnit $productUnit = null): Product
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

    protected function getEntity(string $className, int|string $id): object
    {
        static $entities = [];
        if (!isset($entities[$className][$id])) {
            $entities[$className][$id] = new $className();
            ReflectionUtil::setPropertyValue(
                $entities[$className][$id],
                $this->entityManager->getClassMetadata($className)->getSingleIdentifierFieldName(),
                $id
            );
        }

        return $entities[$className][$id];
    }

    protected function getFormBuilder(): FormBuilderInterface
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())
            ->method('addEventListener')
            ->with(
                FormEvents::PRE_SET_DATA,
                self::logicalAnd(
                    self::isInstanceOf(\Closure::class),
                    self::callback(function (\Closure $closure) {
                        $event = $this->createMock(FormEvent::class);
                        $event->expects(self::any())
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
