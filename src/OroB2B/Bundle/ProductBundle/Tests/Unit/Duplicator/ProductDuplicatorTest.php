<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Duplicator;

use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityRepository;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\AttachmentBundle\Entity\Attachment;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\Manager\AttachmentManager;
use Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

use OroB2B\Bundle\ProductBundle\Duplicator\ProductDuplicator;
use OroB2B\Bundle\ProductBundle\Duplicator\SkuIncrementorInterface;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

class ProductDuplicatorTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_SKU = 'SKU-1';
    const PRODUCT_COPY_SKU = 'SKU-2';
    const UNIT_PRECISION_CODE_1 = 'kg';
    const UNIT_PRECISION_DEFAULT_PRECISION_1 = 2;
    const UNIT_PRECISION_CODE_2 = 'mg';
    const UNIT_PRECISION_DEFAULT_PRECISION_2 = 4;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityManager
     */
    protected $objectManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SkuIncrementorInterface
     */
    protected $skuIncrementor;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttachmentManager
     */
    protected $attachmentManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AttachmentProvider
     */
    protected $attachmentProvider;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|EntityRepository
     */
    protected $productStatusRepository;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AbstractEnumValue
     */
    protected $productStatusDisabled;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Connection
     */
    protected $connection;

    /**
     * @var ProductDuplicator
     */
    protected $duplicator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->objectManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();
        $this->eventDispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->skuIncrementor = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Duplicator\SkuIncrementorInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentManager = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Manager\AttachmentManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->attachmentProvider = $this->getMockBuilder('Oro\Bundle\AttachmentBundle\Provider\AttachmentProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productStatusRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $this->productStatusDisabled = $this->getMockBuilder('Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue')
            ->disableOriginalConstructor()
            ->getMockForAbstractClass();
        $this->connection = $this->getMockBuilder('Doctrine\DBAL\Connection')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ExtendHelper::buildEnumValueClassName('prod_status'))
            ->will($this->returnValue($this->productStatusRepository));

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManager')
            ->with($this->anything())
            ->will($this->returnValue($this->objectManager));

        $this->objectManager->expects($this->any())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));

        $this->productStatusRepository->expects($this->once())
            ->method('find')
            ->with(Product::STATUS_DISABLED)
            ->will($this->returnValue($this->productStatusDisabled));

        $this->duplicator = new ProductDuplicator(
            $this->doctrineHelper,
            $this->eventDispatcher,
            $this->attachmentManager,
            $this->attachmentProvider
        );

        $this->duplicator->setSkuIncrementor($this->skuIncrementor);
    }

    public function testDuplicate()
    {
        $image = new File();
        $imageCopy = new File();

        $attachmentFile1 = new File();
        $attachmentFileCopy1 = new File();
        $attachmentFile2 = new File();
        $attachmentFileCopy2 = new File();

        $attachment1 = (new Attachment())
            ->setFile($attachmentFile1);
        $attachment2 = (new Attachment())
            ->setFile($attachmentFile2);

        $product = (new StubProduct())
            ->setSku(self::PRODUCT_SKU)
            ->addUnitPrecision($this->prepareUnitPrecision(
                self::UNIT_PRECISION_CODE_1,
                self::UNIT_PRECISION_DEFAULT_PRECISION_1
            ))
            ->addUnitPrecision($this->prepareUnitPrecision(
                self::UNIT_PRECISION_CODE_2,
                self::UNIT_PRECISION_DEFAULT_PRECISION_2
            ))
            ->setImage($image);

        $this->skuIncrementor->expects($this->once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->will($this->returnValue(self::PRODUCT_COPY_SKU));

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($product)
            ->will($this->returnValue([$attachment1, $attachment2]));

        $this->attachmentManager->expects($this->any())
            ->method('copyAttachmentFile')
            ->with($image)
            ->will($this->returnValue($imageCopy));
        $this->attachmentManager->expects($this->any())
            ->method('copyAttachmentFile')
            ->with($attachmentFile1)
            ->will($this->returnValue($attachmentFileCopy1));
        $this->attachmentManager->expects($this->any())
            ->method('copyAttachmentFile')
            ->with($attachmentFile2)
            ->will($this->returnValue($attachmentFileCopy2));

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $this->connection->expects($this->once())
            ->method('commit');

        $productCopy = $this->duplicator->duplicate($product);
        $productCopyUnitPrecisions = $productCopy->getUnitPrecisions();

        $this->assertEquals(self::PRODUCT_COPY_SKU, $productCopy->getSku());
        $this->assertEquals($this->productStatusDisabled, $productCopy->getStatus());
        $this->assertCount(2, $productCopyUnitPrecisions);
        $this->assertEquals(self::UNIT_PRECISION_CODE_1, $productCopyUnitPrecisions[0]->getUnit()->getCode());
        $this->assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_1,
            $productCopyUnitPrecisions[0]->getUnit()->getDefaultPrecision()
        );
        $this->assertEquals(self::UNIT_PRECISION_CODE_2, $productCopyUnitPrecisions[1]->getUnit()->getCode());
        $this->assertEquals(
            self::UNIT_PRECISION_DEFAULT_PRECISION_2,
            $productCopyUnitPrecisions[1]->getUnit()->getDefaultPrecision()
        );
        $this->assertEquals($imageCopy, $productCopy->getImage());
    }

    public function testDuplicateFailed()
    {
        $product = (new StubProduct())
            ->setSku(self::PRODUCT_SKU);

        $this->skuIncrementor->expects($this->once())
            ->method('increment')
            ->with(self::PRODUCT_SKU)
            ->will($this->returnValue(self::PRODUCT_COPY_SKU));

        $this->attachmentProvider->expects($this->once())
            ->method('getEntityAttachments')
            ->with($product)
            ->will($this->returnValue([]));

        $this->connection->expects($this->once())
            ->method('beginTransaction');
        $this->connection->expects($this->once())
            ->method('commit')
            ->will($this->throwException(new \Exception()));
        $this->connection->expects($this->once())
            ->method('rollback');

        $this->setExpectedException('Exception');

        $this->duplicator->duplicate($product);
    }

    /**
     * @param string $code
     * @param int $defaultPrecision
     * @return ProductUnitPrecision
     */
    protected function prepareUnitPrecision($code, $defaultPrecision)
    {
        $productUnit = (new ProductUnit())
            ->setCode($code)
            ->setDefaultPrecision($defaultPrecision);

        return (new ProductUnitPrecision())
            ->setUnit($productUnit);
    }
}
