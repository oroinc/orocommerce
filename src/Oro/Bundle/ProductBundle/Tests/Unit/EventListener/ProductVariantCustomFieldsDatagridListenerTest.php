<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;

class ProductVariantCustomFieldsDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use PropertyAccessTrait;

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
    protected $productClass = 'OroProductBundle:Product';

    /** @var array|string[] */
    protected $variantFields = [self::FIELD_SIZE];

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

        $this->customFieldProvider = $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\CustomFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->customFieldProvider->expects($this->once())
            ->method('getEntityCustomFields')
            ->with($this->productClass)
            ->willReturn($this->productEntityCustomFields);

        $this->productRepository = $this
            ->getMockBuilder('Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository')
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

    public function testOnBuildAfter()
    {
        $this->prepareRepositoryProduct();

        $config = [];
        $this->prepareConfig($config, 'size');

        // Expected will have only size, because it mentioned in variant fields
        $expectedConfig = $config;

        $this->prepareConfig($config, 'color');


        $datagridConfig = DatagridConfiguration::create($config);

        $this->listener->onBuildAfter($this->prepareBuildAfterEvent($datagridConfig));

        $this->assertEquals($expectedConfig, $datagridConfig->toArray());
    }

    /**
     * @param DatagridConfiguration $config
     * @return BuildAfter
     */
    private function prepareBuildAfterEvent(DatagridConfiguration $config)
    {
        return new BuildAfter($this->prepareDatagrid($config));
    }

    private function prepareRepositoryProduct()
    {
        $product = new Product();
        $product->setVariantFields($this->variantFields);
        $product->setSize(self::SIZE);

        $this->productRepository->expects($this->any())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);
    }

    /**
     * @param DatagridConfiguration $config
     * @return DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareDatagrid(DatagridConfiguration $config)
    {
        $parameterBag = new ParameterBag();
        $parameterBag->set('parentProduct', self::PRODUCT_ID);

        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $datagrid->expects($this->any())->method('getParameters')->willReturn($parameterBag);
        $datagrid->expects($this->any())->method('getConfig')->willReturn($config);

        return $datagrid;
    }

    /**
     * @param array $config
     * @param string $field
     */
    private function prepareConfig(&$config, $field)
    {
        $this->getPropertyAccessor()->setValue($config, sprintf('[columns][%s]', $field), ['someSettings']);
        $this->getPropertyAccessor()->setValue($config, sprintf('[sorters][columns][%s]', $field), ['someSettings']);
        $this->getPropertyAccessor()->setValue($config, sprintf('[filters][columns][%s]', $field), ['someSettings']);
    }
}
