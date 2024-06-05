<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\Persistence\ManagerRegistry;
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
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductMiniBlockContentWidgetSettingsType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductMiniBlockContentWidgetSettingsTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testSubmit(): void
    {
        $submittedData = [
            'product' => 42,
            'show_prices' => false,
            'show_add_button' => true,
        ];

        $form = $this->factory->create(ProductMiniBlockContentWidgetSettingsType::class);

        $this->assertEquals(['show_add_button' => true, 'show_prices' => true], $form->getData());

        $form->submit($submittedData);

        $this->assertTrue($form->isValid());
        $this->assertTrue($form->isSynchronized());
        $this->assertEquals($submittedData, $form->getData());
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions(): array
    {
        $entityManager = $this->createMock(EntityManager::class);
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $entityManager->expects($this->any())
            ->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $metadataFactory->expects($this->any())
            ->method('hasMetadataFor')
            ->willReturn(true);

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
                        $entityManager,
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
