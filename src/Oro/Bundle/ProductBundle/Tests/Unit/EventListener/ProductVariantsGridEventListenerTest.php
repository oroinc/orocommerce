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
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ProductVariantsGridEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use PropertyAccessTrait;

    const PRODUCT_ID = 1;

    const FIELD_COLOR = 'color';
    const FIELD_SIZE = 'size';

    const PRODUCT_CLASS = 'stdClass';
    const PRODUCT_DATAGRID_ALIAS = 'product';

    /** @var array */
    protected $fromPart;

    /** @var Product */
    private $parentProduct;

    /** @var DatagridConfiguration */
    private $config;

    /** @var ProductVariantsGridEventListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    private $productRepository;

    /** @var ParameterBag */
    private $parameterBag;

    protected function setUp()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this
            ->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->productRepository);

        $this->parentProduct = new Product();

        $this->fromPart = [];
        $this->setValueByPath($this->fromPart, '[source][query][from]', [
            [
                'table' => self::PRODUCT_CLASS,
                'alias' => self::PRODUCT_DATAGRID_ALIAS,
            ],
        ]);

        $this->config = DatagridConfiguration::create($this->fromPart);
        $this->parameterBag = new ParameterBag();

        $this->listener = new ProductVariantsGridEventListener($doctrineHelper, self::PRODUCT_CLASS);
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

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->fromPart;
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            'product.color is not null',
            'product.size is not null'
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeNoVariantFields()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($this->parentProduct);

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->fromPart;
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', ['1 = 0']);
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeNoParentProduct()
    {
        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->fromPart;
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeNotExistingParentProduct()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);

        $this->productRepository->expects($this->once())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn(null);

        $this->listener->onBuildBefore($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->fromPart;
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
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

    /**
     * @param array|object $target
     * @param string|PropertyPathInterface $path
     * @param mixed $value
     */
    private function setValueByPath(&$target, $path, $value)
    {
        $this->getPropertyAccessor()->setValue($target, $path, $value);
    }
}
