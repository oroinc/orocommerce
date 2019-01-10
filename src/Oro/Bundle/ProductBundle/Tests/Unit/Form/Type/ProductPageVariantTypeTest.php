<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
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
use Symfony\Component\Translation\TranslatorInterface;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        $classMetadata = new ClassMetadata(Product::class);
        $classMetadata->setIdentifier(['id']);

        /** @var EntityManager|\PHPUnit\Framework\MockObject\MockObject $entityManager */
        $entityManager = $this->createMock(EntityManager::class);
        $entityManager
            ->expects(self::any())
            ->method('getClassMetadata')
            ->willReturn($classMetadata);

        $handler = $this->createMock(SearchHandlerInterface::class);
        $handler
            ->expects(self::any())
            ->method('getProperties')
            ->willReturn([]);

        $handler
            ->expects(self::any())
            ->method('getEntityName')
            ->willReturn(Product::class);

        /** @var SearchRegistry|\PHPUnit\Framework\MockObject\MockObject $searchRegistry */
        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry
            ->expects(self::any())
            ->method('getSearchHandler')
            ->will($this->returnValue($handler));

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        return [
            new PreloadedExtension(
                [
                    ProductSelectType::class => new ProductSelectType($translator),
                    OroEntitySelectOrCreateInlineType::class => new OroEntitySelectOrCreateInlineType(
                        $authorizationChecker,
                        $configManager,
                        $entityManager,
                        $searchRegistry
                    ),
                    OroJquerySelect2HiddenType::class => new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $configProvider
                    )
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductPageVariantType::class, null);
        $this->assertTrue($form->has('productPageProduct'));

        $expectedOptions = [
            'autocomplete_alias' => 'oro_all_product_visibility_limited',
            'grid_name' => 'all-products-select-grid'
        ];

        $formOptions = $form->get('productPageProduct')->getConfig()->getOptions();
        $this->assertArraySubset($expectedOptions, $formOptions);

        $this->assertEquals(ProductPageContentVariantType::TYPE, $form->getConfig()->getOption('content_variant_type'));
    }

    public function testGetBlockPrefix()
    {
        $type = new ProductPageVariantType();
        $this->assertEquals(ProductPageVariantType::NAME, $type->getBlockPrefix());
    }
}
