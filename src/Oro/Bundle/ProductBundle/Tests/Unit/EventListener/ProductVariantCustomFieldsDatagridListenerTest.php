<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

class ProductVariantCustomFieldsDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use PropertyAccessTrait;

    const PRODUCT_ID = 1;
    const SIZE = 'L';

    const FIELD_COLOR = 'color';
    const FIELD_SIZE = 'size';

    const LABEL_COLOR = 'Color';
    const LABEL_SIZE = 'Size';

    const PRODUCT_CLASS = 'stdClass';
    const PRODUCT_DATAGRID_ALIAS = 'product';

    /** @var DatagridConfiguration */
    private $config;

    /** @var ProductVariantCustomFieldsDatagridListener */
    private $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ProductRepository */
    private $productRepository;

    /** @var ParameterBag */
    private $parameterBag;

    /** @var \PHPUnit_Framework_MockObject_MockObject|CustomFieldProvider */
    protected $customFieldProvider;

    /** @var array|string[] */
    protected $variantFields = [self::FIELD_SIZE];

    /** @var array */
    protected $productEntityCustomFields = [
        self::FIELD_COLOR => [
            'name' => self::FIELD_COLOR,
            'label' => self::LABEL_COLOR,
        ],
        self::FIELD_SIZE => [
            'name' => self::FIELD_SIZE,
            'label' => self::LABEL_SIZE,
        ],
    ];

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

        $this->customFieldProvider = $this->getMockBuilder('Oro\Bundle\ProductBundle\Provider\CustomFieldProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->productEntityCustomFields);

        $fromPart = $this->getFromPart();
        $this->setValueByPath($fromPart, '[source][query][from]', [
            [
                'table' => self::PRODUCT_CLASS,
                'alias' => self::PRODUCT_DATAGRID_ALIAS,
            ],
        ]);

        $this->config = DatagridConfiguration::create($fromPart);
        $this->parameterBag = new ParameterBag();

        $this->listener = new ProductVariantCustomFieldsDatagridListener(
            $doctrineHelper,
            $this->customFieldProvider,
            self::PRODUCT_CLASS
        );
    }

    public function testOnBuildBeforeHideUnsuitable()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->prepareRepositoryProduct($product);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getFromPart();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            'product.color is not null',
            'product.size is not null',
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableNoVariantFields()
    {
        $product = new Product();
        $this->prepareRepositoryProduct($product);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getFromPart();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', ['1 = 0']);
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableNoParentProduct()
    {
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getFromPart();
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableNotExistingParentProduct()
    {
        $this->prepareRepositoryProduct(null);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getFromPart();
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildAfter()
    {
        $product = new StubProduct();
        $product->setVariantFields($this->variantFields);
        $product->setSize(self::SIZE);
        $this->prepareRepositoryProduct($product);

        $config = [];
        $this->prepareConfig($config, 'size');

        // Expected will have only size, because it mentioned in variant fields
        $expectedConfig = $config;

        $this->prepareConfig($config, 'color');
        $datagridConfig = DatagridConfiguration::create($config);

        $this->setParameterBag();
        $this->listener->onBuildAfter($this->prepareBuildAfterEvent($datagridConfig));

        $this->assertEquals($expectedConfig, $datagridConfig->toArray());
    }

    /**
     * @param array $config
     * @param string $field
     */
    private function prepareConfig(&$config, $field)
    {
        $this->setValueByPath($config, sprintf('[columns][%s]', $field), ['someSettings']);
        $this->setValueByPath($config, sprintf('[sorters][columns][%s]', $field), ['someSettings']);
        $this->setValueByPath($config, sprintf('[filters][columns][%s]', $field), ['someSettings']);
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
     * @param DatagridConfiguration $config
     * @return BuildAfter
     */
    private function prepareBuildAfterEvent(DatagridConfiguration $config)
    {
        return new BuildAfter($this->prepareDatagrid($config));
    }

    /**
     * @param DatagridConfiguration $config
     * @return DatagridInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function prepareDatagrid(DatagridConfiguration $config = null)
    {
        $datagrid = $this->getMock(DatagridInterface::class);
        $datagrid->expects($this->any())
            ->method('getParameters')
            ->willReturn($this->parameterBag);

        if ($config) {
            $datagrid->expects($this->any())
                ->method('getConfig')
                ->willReturn($config);
        }

        return $datagrid;
    }

    /**
     * @param Product|null $product
     */
    private function prepareRepositoryProduct(Product $product = null)
    {
        $this->productRepository->expects($this->any())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);
    }

    /**
     * @return array
     */
    private function getFromPart()
    {
        $fromPart = [];
        $this->setValueByPath($fromPart, '[source][query][from]', [
            [
                'table' => self::PRODUCT_CLASS,
                'alias' => self::PRODUCT_DATAGRID_ALIAS,
            ],
        ]);

        return $fromPart;
    }

    private function setParameterBag()
    {
        $this->parameterBag->set('parentProduct', self::PRODUCT_ID);
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
