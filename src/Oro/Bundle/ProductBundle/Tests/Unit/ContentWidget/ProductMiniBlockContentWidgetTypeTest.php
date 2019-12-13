<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\ContentWidget;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityManager;
use Oro\Bundle\CMSBundle\Entity\ContentWidget;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\ProductBundle\ContentWidget\ProductMiniBlockContentWidgetType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ProductMiniBlockContentWidgetTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ObjectRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $repository;

    /** @var ObjectManager|\PHPUnit\Framework\MockObject\MockObject */
    private $manager;

    /** @var ProductMiniBlockContentWidgetType */
    private $contentWidgetType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = $this->createMock(ObjectRepository::class);

        $this->manager = $this->createMock(ObjectManager::class);
        $this->manager->expects($this->any())
            ->method('getRepository')
            ->with(Product::class)
            ->willReturn($this->repository);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(Product::class)
            ->willReturn($this->manager);

        $this->contentWidgetType = new ProductMiniBlockContentWidgetType($registry);
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

    public function testGetWidgetData(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['product' => 42]);

        $product = $this->getEntity(Product::class, ['id' => 42]);

        $this->repository->expects($this->any())
            ->method('find')
            ->with(42)
            ->willReturn($product);

        $this->assertSame(
            ['product' => $product],
            $this->contentWidgetType->getWidgetData($contentWidget)
        );
    }

    public function testGetSettingsForm(): void
    {
        $form = $this->contentWidgetType->getSettingsForm(new ContentWidget(), $this->factory);

        $this->assertInstanceOf(
            ProductMiniBlockContentWidgetSettingsType::class,
            $form->getConfig()->getType()->getInnerType()
        );
    }

    public function testGetBackOfficeViewSubBlocks(): void
    {
        $contentWidget = new ContentWidget();
        $contentWidget->setName('test_name');
        $contentWidget->setSettings(['product' => 42]);

        $product = new Product();

        $this->repository->expects($this->any())
            ->method('find')
            ->with(42)
            ->willReturn($product);

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with(
                '@OroProduct/ProductMiniBlockContentWidget/options.html.twig',
                ['product' => $product]
            )
            ->willReturn('rendered settings template');

        $this->assertEquals(
            [
                [
                    'title' => 'oro.product.sections.options',
                    'subblocks' => [['data' => ['rendered settings template']]]
                ],
            ],
            $this->contentWidgetType->getBackOfficeViewSubBlocks($contentWidget, $twig)
        );
    }

    public function testGetDefaultTemplate(): void
    {
        $contentWidget = new ContentWidget();

        $twig = $this->createMock(Environment::class);

        $this->assertEquals('', $this->contentWidgetType->getDefaultTemplate($contentWidget, $twig));
    }

    /**
     * @return array
     */
    protected function getExtensions(): array
    {
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())
            ->method('getConfig')
            ->willReturnCallback(
                static function ($className, $fieldName) {
                    return new Config(
                        new FieldConfigId(
                            'form',
                            $className,
                            $fieldName
                        ),
                        [
                            'grid_name' => 'test_grid'
                        ]
                    );
                }
            );

        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())
            ->method('getProvider')
            ->with('form')
            ->willReturn($configProvider);

        $searchHandler = $this->createMock(SearchHandlerInterface::class);
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
                        $configManager,
                        $this->createMock(EntityManager::class),
                        $searchRegistry
                    ),
                    OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                        $this->createMock(EntityManager::class),
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getTypeExtensions(): array
    {
        return array_merge(
            parent::getExtensions(),
            [
                new DataBlockExtension(),
            ]
        );
    }
}
