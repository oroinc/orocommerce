<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductVariantsGridEventListener;

class ProductVariantsGridEventListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRODUCT_ID = 1;

    const FIELD_COLOR = 'color';
    const FIELD_SIZE = 'size';

    /** @var Product */
    private $parentProduct;

    /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridConfiguration */
    private $config;

    /** @var ProductVariantsGridEventListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    private $productRepository;

    /** @var ParameterBag */
    private $parameterBag;

    public function setUp()
    {
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this
            ->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->willReturn($this->productRepository);

        $this->parentProduct = new Product();

        $this->listener = new ProductVariantsGridEventListener($doctrineHelper);

        $this->config = $this->getMockBuilder(DatagridConfiguration::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->parameterBag = new ParameterBag();
    }

    public function testOnBuildBefore()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($this->parentProduct);

        $this->parentProduct->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->config->expects($this->exactly(2))
            ->method('offsetAddToArrayByPath')
            ->withConsecutive(
                ['[source][query][where][and]', ['product.color is not null']],
                ['[source][query][where][and]', ['product.size is not null']]
            );

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));
    }

    public function testOnBuildBeforeNoVariantFields()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($this->parentProduct);

        $this->config->expects($this->never())
            ->method('offsetAddToArrayByPath');

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));
    }

    public function testOnBuildBeforeNoParentProduct()
    {
        $this->config->expects($this->never())
            ->method('offsetAddToArrayByPath');

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));
    }

    public function testOnBuildBeforeNotExistingParentProduct()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn(null);

        $this->config->expects($this->never())
            ->method('offsetAddToArrayByPath');

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));
    }

    /**
     * @param DatagridConfiguration $config
     * @return BuildBefore
     */
    private function prepareBuildBeforeEvent(DatagridConfiguration $config)
    {
        return new BuildBefore($this->prepareDatagrid(), $config);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DatagridInterface
     */
    private function prepareDatagrid()
    {
        $datagrid = $this->getMock(DatagridInterface::class);
        $datagrid->expects($this->once())
            ->method('getParameters')
            ->willReturn($this->parameterBag);

        return $datagrid;
    }
}