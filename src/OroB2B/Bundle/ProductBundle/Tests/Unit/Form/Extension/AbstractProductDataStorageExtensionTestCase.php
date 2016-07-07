<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\Mapping\ClassMetadata;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Extension\Stub\ProductDataStorageExtensionStub;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 */
abstract class AbstractProductDataStorageExtensionTestCase extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductDataStorage */
    protected $storage;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|Request */
    protected $request;

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
    protected function setUp()
    {
        $this->storage = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Storage\ProductDataStorage')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();

        $this->request = $this->getMock('Symfony\Component\HttpFoundation\Request');
        /** @var RequestStack|\PHPUnit_Framework_MockObject_MockObject $requestStack */
        $requestStack = $this->getMock('Symfony\Component\HttpFoundation\RequestStack');
        $requestStack->expects($this->any())->method('getCurrentRequest')->willReturn($this->request);

        $this->extension = new ProductDataStorageExtensionStub(
            $requestStack,
            $this->storage,
            $this->doctrineHelper,
            $this->productClass
        );

        $this->extension->setDataClass($this->dataClass);

        $this->entity = new \stdClass();
    }

    protected function tearDown()
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

        $this->storage->expects($this->never())->method('get');

        $this->extension->buildForm($this->getBuilderMock(true), []);
    }

    public function testBuildFormNoData()
    {
        $this->doctrineHelper->expects($this->once())->method('getSingleEntityIdentifier')->willReturn(null);
        $this->doctrineHelper->expects($this->never())->method('getEntityRepository');

        $this->assertRequestGetCalled();
        $this->assertStorageCalled([], true);

        $this->extension->buildForm($this->getBuilderMock(true), []);
    }

    /**
     * @param string $sku
     * @param ProductUnit|null $productUnit
     * @return Product
     */
    protected function getProductEntity($sku, ProductUnit $productUnit = null)
    {
        $product = new StubProduct();
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
        $this->request->expects($this->once())
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
        $this->storage->expects($this->once())
            ->method('get')
            ->willReturn($data);
        $this->storage->expects($expectsRemove ? $this->once() : $this->never())
            ->method('remove');
    }

    /**
     * @param Product $product
     */
    protected function assertProductRepositoryCalled(Product $product)
    {
        $repo = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repo->expects($this->once())
            ->method('findOneBySku')
            ->with($product->getSku())
            ->willReturn($product);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with($this->productClass)
            ->willReturn($repo);
    }

    /**
     * @param array $mappings
     */
    protected function assertMetadataCalled(array $mappings = [])
    {
        /** @var ClassMetadata|\PHPUnit_Framework_MockObject_MockObject $metadata */
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

    /**
     * @param array $mappings
     */
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
        $ident = $this->doctrineHelper->getEntityMetadata($className)->getSingleIdentifierFieldName();

        return $ident ?: $default;
    }

    public function testExtendedType()
    {
        $type = 'entity';
        $this->extension->setExtendedType($type);
        $this->assertSame($type, $this->extension->getExtendedType());
    }

    /**
     * @param bool $expectsAddEventListener
     * @return \PHPUnit_Framework_MockObject_MockObject|FormBuilderInterface
     */
    protected function getBuilderMock($expectsAddEventListener = false)
    {
        /** @var  $builder */
        $builder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        if ($expectsAddEventListener) {
            $builder->expects($this->once())->method('addEventListener')->with(
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
