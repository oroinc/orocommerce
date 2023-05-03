<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Event\BuildAfter;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeFamilyRepository;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\EventListener\ProductVariantCustomFieldsDatagridListener;
use Oro\Bundle\ProductBundle\Provider\CustomFieldProvider;
use Oro\Bundle\ProductBundle\Provider\VariantField;
use Oro\Bundle\ProductBundle\Provider\VariantFieldProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product as StubProduct;
use Oro\Component\Testing\Unit\PropertyAccess\PropertyAccessTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVariantCustomFieldsDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    use PropertyAccessTrait;

    private const PRODUCT_ID = 1;
    private const SIZE = 'L';

    private const FIELD_COLOR = 'color';
    private const FIELD_SIZE = 'size';

    private const LABEL_COLOR = 'Color';
    private const LABEL_SIZE = 'Size';

    private const PRODUCT_CLASS = 'productClass';
    private const PRODUCT_ALIAS = 'product';

    private const PRODUCT_VARIANT_LINK_CLASS = 'productVariantLinkClass';
    private const PRODUCT_VARIANT_LINK_ALIAS = 'productVariantLinkAlias';

    private const DATAGRID_NAME = 'Datagrid name';

    /** @var DatagridConfiguration */
    private $config;

    /** @var ProductVariantCustomFieldsDatagridListener */
    private $listener;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ProductRepository */
    private $productRepository;

    /** @var ParameterBag */
    private $parameterBag;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CustomFieldProvider */
    private $customFieldProvider;

    /** @var \PHPUnit\Framework\MockObject\MockObject|VariantFieldProvider */
    private $variantFieldProvider;

    /** @var array|string[] */
    private $variantFields = [self::FIELD_SIZE];

    /** @var array */
    private $productEntityCustomFields = [
        self::FIELD_COLOR => [
            'name' => self::FIELD_COLOR,
            'label' => self::LABEL_COLOR,
        ],
        self::FIELD_SIZE => [
            'name' => self::FIELD_SIZE,
            'label' => self::LABEL_SIZE,
        ],
    ];

    /** @var \PHPUnit\Framework\MockObject\MockObject|DoctrineHelper */
    private $doctrineHelper;

    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->customFieldProvider = $this->createMock(CustomFieldProvider::class);

        $this->customFieldProvider->expects($this->any())
            ->method('getEntityCustomFields')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->productEntityCustomFields);

        $initConfig = $this->getInitConfig();

        $this->config = DatagridConfiguration::create($initConfig);
        $this->parameterBag = new ParameterBag();

        $this->variantFieldProvider = $this->createMock(VariantFieldProvider::class);

        $this->listener = new ProductVariantCustomFieldsDatagridListener(
            $this->doctrineHelper,
            $this->customFieldProvider,
            $this->variantFieldProvider,
            self::PRODUCT_CLASS,
            self::PRODUCT_VARIANT_LINK_CLASS
        );
    }

    /**
     * User open configurable product and see all available product variants
     * for configurable product with 2 variant fields (color, size)
     */
    public function testOnBuildBeforeHideUnsuitableShowsAllAvailableVariants()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->prepareRepositoryProduct($product);

        $this->setParameterBag();
        $event = $this->prepareBuildBeforeEvent($this->config);
        $this->listener->onBuildBeforeHideUnsuitable($event);

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            sprintf('%s.color IS NOT NULL', self::PRODUCT_ALIAS),
            sprintf('%s.size IS NOT NULL', self::PRODUCT_ALIAS),
        ]);

        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableShowsAllAvailableVariantsOnCreate()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->prepareRepositoryProduct($product);

        $this->setParameterBag(null);
        $event = $this->prepareBuildBeforeEvent($this->config);
        $this->listener->onBuildBeforeHideUnsuitable($event);

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', ['1 = 0' ]);

        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    /**
     * User saves configurable product with "color" variant field and select products with ids 1,2,3 as variants
     */
    public function testOnBuildBeforeHideUnsuitableAfterSubmit()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
        ]);

        $this->prepareRepositoryProduct($product);

        $formAppendVariants = '1,2,3';

        $this->setParameterBag();
        $this->parameterBag->set(ProductVariantCustomFieldsDatagridListener::FORM_APPEND_VARIANTS, $formAppendVariants);

        $event = $this->prepareBuildBeforeEvent($this->config);
        $this->listener->onBuildBeforeHideUnsuitable($event);

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            sprintf('%s.color IS NOT NULL', self::PRODUCT_ALIAS),
        ]);

        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IN (%s)', self::PRODUCT_ALIAS, $formAppendVariants),
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    /**
     * User submit form, got any validation error and then changes variant fields list
     */
    public function testOnBuildBeforeHideUnsuitableAfterSubmitAndChangeVariantFields()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $dataIn = [10, 11];

        $dynamicFields = [
            'selectedVariantFields' => [
                self::FIELD_COLOR
            ],
            'data_in' => $dataIn,
            'gridDynamicLoad' => '1'
        ];

        $this->parameterBag->set('_parameters', $dynamicFields);

        $formAppendVariants = '1,2,3';

        $this->setParameterBag();
        $this->prepareRepositoryProduct($product);

        $this->parameterBag->set(ProductVariantCustomFieldsDatagridListener::FORM_APPEND_VARIANTS, $formAppendVariants);

        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            sprintf('%s.color IS NOT NULL', self::PRODUCT_ALIAS),
        ]);

        $expectedAppendVariants = implode(',', $dataIn);

        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IN (%s)', self::PRODUCT_ALIAS, $expectedAppendVariants),
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableWithDynamicFields()
    {
        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $dynamicFields = [
            'selectedVariantFields' => [
                self::FIELD_COLOR
            ],
        ];

        $this->parameterBag->set('_parameters', $dynamicFields);

        $this->prepareRepositoryProduct($product);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', [
            sprintf('%s.color IS NOT NULL', self::PRODUCT_ALIAS),
        ]);

        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableEmptyDynamicParams()
    {
        $product = new Product();
        $this->prepareRepositoryProduct($product);

        $dynamicFields = [
            ProductVariantCustomFieldsDatagridListener::FORM_SELECTED_VARIANTS => 0
        ];

        $this->parameterBag->set('_parameters', $dynamicFields);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', ['1 = 0']);
        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableWithoutFrom()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('A root entity is missing for grid "Datagrid name"');

        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->prepareRepositoryProduct($product);

        $this->config->offsetUnsetByPath('[source][query][from]');

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));
    }

    public function testOnBuildBeforeHideUnsuitableWithoutCorrectVariantLinkJoin()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'A left join with "productVariantLinkClass" is missing for grid "Datagrid name"'
        );

        $product = new Product();
        $product->setVariantFields([
            self::FIELD_COLOR,
            self::FIELD_SIZE,
        ]);

        $this->prepareRepositoryProduct($product);

        $this->config->offsetSetByPath('[source][query][join][left]', [
            [
                'join' => 'notVariantLinkClass',
                'alias' => 'notVariantLinkClassAlias',
            ],
        ]);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));
    }

    public function testOnBuildBeforeHideUnsuitableNoVariantFields()
    {
        $product = new Product();
        $this->prepareRepositoryProduct($product);

        $this->setParameterBag();
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getInitConfig();
        $this->setValueByPath($expectedConfigValue, '[source][query][where][and]', ['1 = 0']);
        $this->setValueByPath($expectedConfigValue, '[source][query][where][or]', [
            sprintf('%s.id IS NOT NULL', self::PRODUCT_VARIANT_LINK_ALIAS)
        ]);

        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableNoParentProduct()
    {
        $this->listener->onBuildBeforeHideUnsuitable($this->prepareBuildBeforeEvent($this->config));

        $expectedConfigValue = $this->getInitConfig();
        $this->assertEquals($expectedConfigValue, $this->config->toArray());
    }

    public function testOnBuildBeforeHideUnsuitableNotExistingParentProduct()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Can not find parent product with id "1"');

        $this->prepareRepositoryProduct(null);

        $this->setParameterBag();
        $event = $this->prepareBuildBeforeEvent($this->config);
        $this->listener->onBuildBeforeHideUnsuitable($event);
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

    public function testOnBuildAfterEditGrid()
    {
        $attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository);

        $attributeFamily = new AttributeFamily();

        $attributeFamilyRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($attributeFamily);

        $config = [];
        $this->prepareConfig($config, 'size');
        $this->prepareConfig($config, 'color');
        // Expected color and size both
        $expectedConfig = $config;

        $datagridConfig = DatagridConfiguration::create($config);

        $this->setParameterBag();
        $this->variantFieldProvider->expects($this->once())
            ->method('getVariantFields')
            ->with($attributeFamily)
            ->willReturn([
                'color' => new VariantField('color', 'color'),
                'size' => new VariantField('size', 'size'),
            ]);
        $this->listener->onBuildAfterEditGrid($this->prepareBuildAfterEvent($datagridConfig));

        $this->assertEquals($expectedConfig, $datagridConfig->toArray());
    }

    public function testOnBuildAfterEditGridWithNoFamily()
    {
        $attributeFamilyRepository = $this->createMock(AttributeFamilyRepository::class);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(AttributeFamily::class)
            ->willReturn($attributeFamilyRepository);

        $attributeFamilyRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn(null);

        $config = [];
        $datagridConfig = DatagridConfiguration::create($config);

        $this->setParameterBag();
        $this->variantFieldProvider->expects($this->never())
            ->method('getVariantFields');
        $this->listener->onBuildAfterEditGrid($this->prepareBuildAfterEvent($datagridConfig));
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

    private function prepareBuildBeforeEvent(DatagridConfiguration $config): BuildBefore
    {
        return new BuildBefore($this->getDatagrid(), $config);
    }

    private function prepareBuildAfterEvent(DatagridConfiguration $config): BuildAfter
    {
        return new BuildAfter($this->getDatagrid($config));
    }

    private function getDatagrid(DatagridConfiguration $config = null): DatagridInterface
    {
        $datagrid = $this->createMock(DatagridInterface::class);
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

    private function prepareRepositoryProduct(Product $product = null)
    {
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepository')
            ->with(self::PRODUCT_CLASS)
            ->willReturn($this->productRepository);

        $this->productRepository->expects($this->any())
            ->method('find')
            ->with(self::PRODUCT_ID)
            ->willReturn($product);
    }

    private function getInitConfig(): array
    {
        $initConfig = [];
        $this->setValueByPath($initConfig, '[source][query][from]', [
            [
                'table' => self::PRODUCT_CLASS,
                'alias' => self::PRODUCT_ALIAS,
            ],
        ]);

        $this->setValueByPath($initConfig, '[source][query][join][left]', [
            [
                'join' => self::PRODUCT_VARIANT_LINK_CLASS,
                'alias' => self::PRODUCT_VARIANT_LINK_ALIAS,
            ],
        ]);

        $this->setValueByPath($initConfig, '[name]', self::DATAGRID_NAME);

        return $initConfig;
    }

    /**
     * @param int $productId
     */
    private function setParameterBag($productId = self::PRODUCT_ID)
    {
        $this->parameterBag->set('parentProduct', $productId);
        $this->parameterBag->set('attributeFamily', 1);
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
