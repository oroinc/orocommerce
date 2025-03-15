<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentWidget;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\EntityConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\ProductBundle\ContentWidget\ProductMiniBlockContentWidgetType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Bundle\ProductBundle\Model\ProductView;
use Oro\Bundle\ProductBundle\Provider\ProductListBuilder;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ProductMiniBlockContentWidgetTypeTest extends FormIntegrationTestCase
{
    private const PRODUCT_LIST_TYPE = 'product_mini_block';

    private ProductListBuilder&MockObject $productListBuilder;
    private ProductMiniBlockContentWidgetType $contentWidgetType;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->productListBuilder = $this->createMock(ProductListBuilder::class);

        $this->contentWidgetType = new ProductMiniBlockContentWidgetType(
            $this->productListBuilder
        );
    }

    public function testGetName(): void
    {
        $this->assertEquals('product_mini_block', $this->contentWidgetType::getName());
    }

    public function testGetLabel(): void
    {
        $this->assertEquals(
            'oro.product.content_widget_type.product_mini_block.label',
            $this->contentWidgetType->getLabel()
        );
    }

    public function testIsInline(): void
    {
        $this->assertFalse($this->contentWidgetType->isInline());
    }

    public function testGetSettingsForm(): void
    {
        $form = $this->contentWidgetType->getSettingsForm(new ContentWidget(), $this->factory);

        $this->assertInstanceOf(
            ProductMiniBlockContentWidgetSettingsType::class,
            $form->getConfig()->getType()->getInnerType()
        );
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();

        $twig = $this->createMock(Environment::class);

        $this->assertEquals('', $this->contentWidgetType->getDefaultTemplate($contentWidget, $twig));
    }

    public function testGetWidgetData(): void
    {
        $productId = 42;
        $productView = new ProductView();
        $productView->set('id', $productId);

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['product' => $productId]);

        $this->productListBuilder->expects($this->exactly(2))
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn([$productView]);

        $this->assertSame(
            ['product' => $productView, 'instanceNumber' => 0],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
        // test that instanceNumber is incremented
        $this->assertSame(
            ['product' => $productView, 'instanceNumber' => 1],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataWhenProductNotVisible(): void
    {
        $productId = 42;
        $productView = new ProductView();
        $productView->set('id', $productId);

        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['product' => $productId]);

        $this->productListBuilder->expects($this->once())
            ->method('getProductsByIds')
            ->with(self::PRODUCT_LIST_TYPE, [$productId])
            ->willReturn([]);

        $this->assertSame(
            ['product' => null, 'instanceNumber' => 0],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetWidgetDataWhenNoProductId(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings([]);

        $this->productListBuilder->expects($this->never())
            ->method('getProductsByIds');

        $this->assertSame(
            ['instanceNumber' => 0],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    #[\Override]
    protected function getExtensions(): array
    {
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($this->createMock(EntityManagerInterface::class));

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('hasConfig')
            ->willReturn(true);
        $configManager->expects($this->any())
            ->method('getEntityConfig')
            ->with('form')
            ->willReturnCallback(function ($className) {
                return new Config(new EntityConfigId('form', $className), ['grid_name' => 'test_grid']);
            });

        $searchHandler = $this->createMock(SearchHandlerInterface::class);
        $searchHandler->expects($this->any())
            ->method('getEntityName')
            ->willReturn(Product::class);
        $searchHandler->expects($this->any())
            ->method('getProperties')
            ->willReturn(['code', 'label']);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects($this->any())
            ->method('getSearchHandler')
            ->willReturn($searchHandler);

        return [
            new PreloadedExtension(
                [
                    ProductMiniBlockContentWidgetSettingsType::class => new ProductMiniBlockContentWidgetSettingsType(
                        $this->createMock(ManagerRegistry::class)
                    ),
                    ProductSelectType::class => new ProductSelectType($this->createMock(TranslatorInterface::class)),
                    OroEntitySelectOrCreateInlineType::class => new OroEntitySelectOrCreateInlineType(
                        $this->createMock(AuthorizationCheckerInterface::class),
                        $this->createMock(FeatureChecker::class),
                        $configManager,
                        $doctrine,
                        $searchRegistry
                    ),
                    OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                        $doctrine,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    #[\Override]
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension()
            ]
        );
    }
}
