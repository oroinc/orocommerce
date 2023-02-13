<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\FormBundle\Autocomplete\SearchHandlerInterface;
use Oro\Bundle\FormBundle\Autocomplete\SearchRegistry;
use Oro\Bundle\FormBundle\Form\Type\OroEntitySelectOrCreateInlineType;
use Oro\Bundle\FormBundle\Form\Type\OroJquerySelect2HiddenType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductPageVariantType;
use Oro\Bundle\ProductBundle\Form\Type\ProductSelectType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function getExtensions(): array
    {
        $classMetadata = new ClassMetadata(Product::class);
        $classMetadata->setIdentifier(['id']);

        $entityManager = $this->createMock(EntityManager::class);
        $entityManager->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $handler = $this->createMock(SearchHandlerInterface::class);
        $handler->expects(self::any())
            ->method('getProperties')
            ->willReturn([]);
        $handler->expects(self::any())
            ->method('getEntityName')
            ->willReturn(Product::class);

        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry->expects(self::any())
            ->method('getSearchHandler')
            ->willReturn($handler);

        return [
            new PreloadedExtension(
                [
                    new ProductSelectType($this->createMock(TranslatorInterface::class)),
                    new OroEntitySelectOrCreateInlineType(
                        $this->createMock(AuthorizationCheckerInterface::class),
                        $this->createMock(FeatureChecker::class),
                        $this->createMock(ConfigManager::class),
                        $entityManager,
                        $searchRegistry
                    ),
                    new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $this->createMock(ConfigProvider::class)
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductPageVariantType::class);

        $this->assertTrue($form->has('productPageProduct'));
        $productPageProductOptions = $form->get('productPageProduct')->getConfig()->getOptions();
        $this->assertSame('oro_all_product_visibility_limited', $productPageProductOptions['autocomplete_alias']);
        $this->assertSame('all-products-select-grid', $productPageProductOptions['grid_name']);

        $this->assertEquals(ProductPageContentVariantType::TYPE, $form->getConfig()->getOption('content_variant_type'));
    }

    public function testGetBlockPrefix()
    {
        $type = new ProductPageVariantType();
        $this->assertEquals(ProductPageVariantType::NAME, $type->getBlockPrefix());
    }
}
