<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Genemu\Bundle\FormBundle\Form\JQuery\Type\Select2Type;
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
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Translation\TranslatorInterface;

class ProductPageVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductPageVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->type = new ProductPageVariantType();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        /** @var AuthorizationCheckerInterface||\PHPUnit_Framework_MockObject_MockObject $authorizationChecker */
        $authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);

        $classMetadata = new ClassMetadata(Product::class);
        $classMetadata->setIdentifier(['id']);

        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $entityManager */
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

        /** @var SearchRegistry|\PHPUnit_Framework_MockObject_MockObject $searchRegistry */
        $searchRegistry = $this->createMock(SearchRegistry::class);
        $searchRegistry
            ->expects(self::any())
            ->method('getSearchHandler')
            ->will($this->returnValue($handler));

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);

        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(TranslatorInterface::class);

        return [
            new PreloadedExtension(
                [
                    ProductSelectType::class => new ProductSelectType($translator),
                    OroEntitySelectOrCreateInlineType::NAME => new OroEntitySelectOrCreateInlineType(
                        $authorizationChecker,
                        $configManager,
                        $entityManager,
                        $searchRegistry
                    ),
                    OroJquerySelect2HiddenType::NAME => new OroJquerySelect2HiddenType(
                        $entityManager,
                        $searchRegistry,
                        $configProvider
                    ),
                    'genemu_jqueryselect2_hidden' => new Select2Type('hidden')
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('productPageProduct'));

        $expectedOptions = [
            'autocomplete_alias' => 'oro_all_product_visibility_limited',
            'grid_name' => 'all-products-select-grid'
        ];

        $formOptions = $form->get('productPageProduct')->getConfig()->getOptions();
        $this->assertArraySubset($expectedOptions, $formOptions);

        $this->assertEquals(ProductPageContentVariantType::TYPE, $form->getConfig()->getOption('content_variant_type'));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductPageVariantType::NAME, $this->type->getBlockPrefix());
    }
}
