<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;

use OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use OroB2B\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener;
use OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Entity\Stub\StubProduct;

class ProductVariantCustomFieldsDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_ID = 1;
    const SIZE = 'L';

    const FIELD_COLOR = 'color';
    const FIELD_SIZE = 'size';

    const LABEL_COLOR = 'Color';
    const LABEL_SIZE = 'Size';

    /** @var ProductVariantCustomFieldsDatagridListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper */
    protected $doctrineHelper;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CustomFieldProvider */
    protected $customFieldProvider;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    protected $productRepository;

    /** @var string */
    protected $productClass = 'OroB2BProductBundle:Product';

    /** @var string[] */
    protected $parentProductCustomFields = [self::FIELD_SIZE];

    /** @var array */
    protected $productEntityCustomFields = [
        self::FIELD_COLOR => [
            'name' => self::FIELD_COLOR,
            'label' => self::LABEL_COLOR
        ],
        self::FIELD_SIZE => [
            'name' => self::FIELD_SIZE,
            'label' => self::LABEL_SIZE
        ]
    ];

    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder('Oro\Bundle\EntityBundle\ORM\DoctrineHelper')
            ->disableOriginalConstructor()
            ->getMock();

        $this->customFieldProvider = $this->getMockBuilder('OroB2B\Bundle\ProductBundle\Provider\CustomFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($this->productClass)
            ->willReturn($this->productEntityCustomFields);

        $this->productRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ProductBundle\Entity\Repository\ProductRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->willReturn($this->productRepository);

        $this->listener = new ProductVariantCustomFieldsDatagridListener(
            $this->doctrineHelper,
            $this->customFieldProvider,
            $this->productClass
        );
    }

    protected function tearDown()
    {
        unset($this->listener, $this->doctrineHelper, $this->customFieldProvider);
    }

    public function testAddsCustomFieldLabelsBeforeBuild()
    {
        $this->prepareRepositoryProduct();

        $config = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration')
            ->disableOriginalConstructor()
            ->getMock();
        $config->expects($this->at(0))
            ->method('offsetSetByPath')
            ->with('[columns][' . self::FIELD_SIZE . ']', ['label' => self::LABEL_SIZE]);

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($config));
    }

    public function testAddsCustomFieldValuesAfterResult()
    {
        $this->prepareRepositoryProduct();

        $resultRecord = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Datasource\ResultRecord')
            ->disableOriginalConstructor()
            ->getMock();
        $resultRecord->expects($this->at(0))
            ->method('getValue')
            ->with('id')
            ->willReturn(self::PRODUCT_ID);
        $resultRecord->expects($this->at(1))
            ->method('addData')
            ->with([self::FIELD_SIZE => self::SIZE]);

        $this->listener->onResultAfter($this->prepareOrmResultAfterEvent($resultRecord));
    }

    /**
     * @param $config
     * @return BuildBefore
     */
    private function prepareBuildBeforeEvent($config)
    {
        return new BuildBefore($this->prepareDatagrid(), $config);
    }

    private function prepareRepositoryProduct()
    {
        $product = new StubProduct();
        $product->setVariantFields($this->parentProductCustomFields);
        $product->setSize(self::SIZE);

        $this->productRepository->expects($this->any())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);
    }

    /**
     * @param $resultRecord
     * @return OrmResultAfter
     */
    private function prepareOrmResultAfterEvent($resultRecord)
    {
        return new OrmResultAfter($this->prepareDatagrid(), [$resultRecord]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareDatagrid()
    {
        $parameterBag = new ParameterBag();
        $parameterBag->set('parentProduct', self::PRODUCT_ID);

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->once())->method('getParameters')->willReturn($parameterBag);

        return $datagrid;
    }
}
