<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOptionInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixColumnQuantityType;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixColumnType;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MatrixColumnTypeTest extends FormIntegrationTestCase
{
    private ProductRFPAvailabilityProvider&MockObject $rfpProvider;
    private ConfigManager&MockObject $configManager;
    private FormBuilderInterface&MockObject $formBuilder;
    private EnumOptionInterface&MockObject $enumOption;
    private MatrixColumnType $type;

    protected function setUp(): void
    {
        $this->enumOption = $this->createMock(EnumOptionInterface::class);
        $this->formBuilder = $this->createMock(FormBuilderInterface::class);
        $this->rfpProvider = $this->createMock(ProductRFPAvailabilityProvider::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->type = new MatrixColumnType($this->rfpProvider, $this->configManager);
        parent::setUp();
    }

    protected function getExtensions(): array
    {
        return [
            new PreloadedExtension([
                MatrixColumnType::class => $this->type,
                MatrixColumnQuantityType::class => new MatrixColumnQuantityType()
            ], [])
        ];
    }

    public function testConfigureOptions(): void
    {
        /** @var OptionsResolver&MockObject $resolver */
        $resolver = $this->createMock(OptionsResolver::class);
        $resolver
            ->expects(self::once())
            ->method('setDefaults')
            ->with(['data_class' => MatrixCollectionColumn::class]);

        $this->type->configureOptions($resolver);
    }

    public function testBuildViewForNotMatrixColumn(): void
    {
        $view = new FormView();
        $form = $this->createMock(FormInterface::class);

        $this->type->buildView($view, $form, []);

        self::assertEquals(
            [
            'value' => null,
            'attr' => []
            ],
            $view->vars
        );
    }

    /**
     * @dataProvider incorrectProductProvider
     */
    public function testBuildViewForIncorrectProduct(?ProductStub $product)
    {
        $column = new MatrixCollectionColumn();
        $view = new FormView();

        $form = $this->createMock(FormInterface::class);

        $column->label = 'Label';
        $column->product = $product;

        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($column);

        $this->type->buildView($view, $form, []);

        self::assertEquals('Label', $view->vars['label']);
        self::assertEquals($product?->getId(), $view->vars['productId']);
        self::assertFalse($view->vars['isEditable']);
    }

    public function testBuildViewWhenProductCanBeAddedToRfqs(): void
    {
        $column = new MatrixCollectionColumn();
        $product = new ProductStub();
        $view = new FormView();

        $form = $this->createMock(FormInterface::class);

        $column->label = 'Label';
        $column->product = $product;

        $product->setId(42);
        $product->setStatus('enabled');

        $this->rfpProvider
            ->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(true);

        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($column);

        $this->type->buildView($view, $form, []);

        self::assertEquals('Label', $view->vars['label']);
        self::assertEquals(42, $view->vars['productId']);
        self::assertTrue($view->vars['isEditable']);
    }

    /**
     * @dataProvider productInventoryStatusProvider
     */
    public function testProductAddingToOrder(string $inventoryStatusId, bool $expected): void
    {
        $column = new MatrixCollectionColumn();
        $product = new ProductStub();
        $view = new FormView();

        $inventoryStatus = $this->createMock(EnumOptionInterface::class);
        $form = $this->createMock(FormInterface::class);

        $column->label = 'Label';
        $column->product = $product;

        $product->setId(42);
        $product->setStatus('enabled');
        $product->set('inventoryStatus', $inventoryStatus);

        $inventoryStatus
            ->expects(self::once())
            ->method('getId')
            ->willReturn($inventoryStatusId);

        $this->rfpProvider
            ->expects(self::once())
            ->method('isProductAllowedForRFP')
            ->with($product)
            ->willReturn(false);

        $this->configManager
            ->expects(self::once())
            ->method('get')
            ->with('oro_order.frontend_product_visibility')
            ->willReturn(['in_stock', 'out_of_stock']);

        $form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($column);

        $this->type->buildView($view, $form, []);

        self::assertEquals('Label', $view->vars['label']);
        self::assertEquals(42, $view->vars['productId']);
        self::assertEquals($expected, $view->vars['isEditable']);
    }

    public static function incorrectProductProvider(): array
    {
        $product = new ProductStub();
        $product->setStatus('disabled');

        return [
            'missed product' => [null],
            'disabled product' => [$product]
        ];
    }

    public static function productInventoryStatusProvider(): array
    {
        return [
            'invalid' => ['invalid', false],
            'valid' => ['in_stock', true],
        ];
    }
}
