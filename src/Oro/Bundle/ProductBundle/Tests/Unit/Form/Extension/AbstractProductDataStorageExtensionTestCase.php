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
use Symfony\Component\Form\FormBuilderInterface;
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
    protected $dataClass = '\stdClass';

    /** @var object */
    protected $entity;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->storage = $this->getMockBuilder('Oro\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->aclHelper = $this->createMock(AclHelper::class);

        $this->request = $this->createMock('Symfony\Component\HttpFoundation\Request');
        /** @var RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack */
        $requestStack = $this->createMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

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

    protected function tearDown(): void
    {
        unset($this->storage, $this->registry, $this->productClass, $this->extension, $this->dataClass);
    }

    public function testBuildFormNoRequestParameter()
    {
        $this->assertRequestGetCalled(false);

        $this->storage->expects($this->never())
            ->method($this->anything());

        $this->extension->buildForm($this->getBuilderMock(), []);
    }

    public function testBuildFormExistingEntity()
    {
        $this->doctrineHelper->expects($this->any())->method('getSingleEntityIdentifier')->willReturn(1);

        $this->assertRequestGetCalled();

        $this->storage->expects($this->any())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);
    }

    public function testBuildFormNoData()
    {
        $this->doctrineHelper->expects($this->never())->method('getSingleEntityIdentifier')->willReturn(null);
        $this->doctrineHelper->expects($this->never())->method('getEntityRepository');

        $this->assertRequestGetCalled();
        $this->assertStorageCalled([], false);

        $this->extension->buildForm($this->getBuilderMock(true), []);
    }

    public function testBuildFormWithWrongPropertiesInData()
    {
        $this->entity->product = null;
        $this->entity->anotherProperty = null;

        $data = [
            ProductDataStorage::ENTITY_DATA_KEY => ['product' => 1, 'notExistsProperty' => 'some_string'],
        ];

        $this->assertMetadataCalled(['product' => ['targetClass' => 'Oro\Bundle\ProductBundle\Entity\Product']]);
        $this->assertRequestGetCalled();
        $this->assertStorageCalled($data);
        $this->assertLoggerNoticeMethodCalled();

        $this->extension->buildForm($this->getBuilderMock(true), []);
    }

    /**
     * @param string $sku
     * @param ProductUnit|null $productUnit
     * @return Product
     */
    protected function getProductEntity($sku, ProductUnit $productUnit = null)
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

    /**
     * @param bool $result
     */
    protected function assertRequestGetCalled($result = true)
    {
        $this->request->expects($this->any())
            ->method('get')
            ->with(ProductDataStorage::STORAGE_KEY)
            ->willReturn($result);
    }

    /**
     * @param array $data
     * @param bool $expectsRemove
     */
    protected function assertStorageCalled(array $data, $expectsRemove = true)
    {
        $this->storage->expects($this->any())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects($expectsRemove ? $this->once() : $this->never())
            ->method('remove');
    }

    protected function assertProductRepositoryCalled(Product $product)
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

        $this->aclHelper
            ->expects($this->once())
            ->method('apply')
            ->with($qb)
            ->willReturn($query);
    }

    protected function assertMetadataCalled(array $mappings = [])
    {
        /** @var ClassMetadata|\PHPUnit\Framework\MockObject\MockObject $metadata */
        $metadata = $this->getMockBuilder('Doctrine\ORM\Mapping\ClassMetadata')
            ->disableOriginalConstructor()
            ->getMock();
        $metadata->expects($this->any())->method('hasAssociation')->will(
            $this->returnCallback(
                function ($property) use ($mappings) {
                    return array_key_exists($property, $mappings);
                }
            )
        );
        $metadata->expects($this->any())->method('getAssociationTargetClass')->will(
            $this->returnCallback(
                function ($property) use ($mappings) {
                    $this->assertArrayHasKey($property, $mappings);
                    $this->assertArrayHasKey('targetClass', $mappings[$property]);

                    return $mappings[$property]['targetClass'];
                }
            )
        );

        $this->doctrineHelper->expects($this->any())->method('getEntityMetadata')->willReturn($metadata);
        $this->doctrineHelper->expects($this->any())->method('getEntityReference')
            ->willReturnCallback([$this, 'getEntity']);
    }

    public function initEntityMetadata(array $mappings = [])
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityMetadata')
            ->willReturnCallback(
                function ($object) use ($mappings) {
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
                }
            );

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->willReturnCallback([$this, 'getEntity']);
    }

    /**
     * @param string $className
     * @param int|string $id
     * @param string $primaryKey
     * @return object
     */
    public function getEntity($className, $id, $primaryKey = 'id')
    {
        static $entities = [];

        if (!isset($entities[$className][$id])) {
            $ident = $this->getPrimaryKey($className, $primaryKey);

            $entity = new $className;
            $reflectionClass = new \ReflectionClass($className);
            $method = $reflectionClass->getProperty($ident);
            $method->setAccessible(true);
            $method->setValue($entity, $id);

            $entities[$className][$id] = $entity;
        }

        return $entities[$className][$id];
    }

    /**
     * @param string $className
     * @param string $default
     * @return string
     */
    protected function getPrimaryKey($className, $default = 'id')
    {
        try {
            return $this->doctrineHelper->getEntityMetadata($className)->getSingleIdentifierFieldName() ?? $default;
        } catch (MappingException $exception) {
            return $default;
        }
    }

    /**
     * @param bool $expectsAddEventListener
     * @return \PHPUnit\Framework\MockObject\MockObject|FormBuilderInterface
     */
    protected function getBuilderMock($expectsAddEventListener = false)
    {
        /** @var  $builder */
        $builder = $this->createMock('Symfony\Component\Form\FormBuilderInterface');
        if ($expectsAddEventListener) {
            $builder->expects($this->any())->method('addEventListener')->with(
                $this->isType('string'),
                $this->logicalAnd(
                    $this->isInstanceOf('\Closure'),
                    $this->callback(
                        function (\Closure $closure) {
                            $event = $this->getMockBuilder('Symfony\Component\Form\FormEvent')
                                ->disableOriginalConstructor()
                                ->getMock();

                            $event->expects($this->any())->method('getData')->willReturn($this->entity);
                            $closure($event);

                            return true;
                        }
                    )
                )
            );
        } else {
            $builder->expects($this->never())->method('addEventListener');
        }

        return $builder;
    }
}
