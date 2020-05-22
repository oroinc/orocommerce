<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionSegmentType;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProductCollectionSegmentTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var EntityProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    private $entityProvider;

    /**
     * @var Manager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $queryDesignerManager;

    /**
     * @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private $doctrineHelper;

    /**
     * @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $tokenStorage;

    /**
     * @var ProductCollectionDefinitionConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    private $definitionConverter;

    /**
     * @var ProductCollectionVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp(): void
    {
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $this->queryDesignerManager = $this->createMock(Manager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->definitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);
        /** @var PropertyAccessor|\PHPUnit\Framework\MockObject\MockObject $propertyAccessor */
        $propertyAccessor = $this->createMock(PropertyAccessor::class);

        $this->type = new ProductCollectionSegmentType($this->definitionConverter, $propertyAccessor);
        parent::setUp();
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects($this->any())
            ->method('getEntityManagerForClass')
            ->with(Product::class, false)
            ->willReturn($em);

        $segmentFilterBuilderType = new SegmentFilterBuilderType(
            $this->doctrineHelper,
            $this->tokenStorage
        );

        /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        /** @var Translator|\PHPUnit\Framework\MockObject\MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    $this->type,
                    SegmentFilterBuilderType::class => $segmentFilterBuilderType
                ],
                [
                    FormType::class => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create(ProductCollectionSegmentType::class, null);

        $this->assertTrue($form->has('includedProducts'));
        $this->assertTrue($form->has('excludedProducts'));
        $this->assertEquals('product-collection-grid', $form->getConfig()->getOption('results_grid'));
    }

    public function testBuildFormWhenAddNameFieldOptionIsTrueAndExistingSegmentGiven()
    {
        $segmentDefinition = '{}';
        $this->definitionConverter
            ->expects($this->any())
            ->method('getDefinitionParts')
            ->with($segmentDefinition)
            ->willReturn([
                'definition' => '{}',
                'included' => [],
                'excluded' => []
            ]);

        $segment = $this->getEntity(Segment::class, ['id' => 1, 'definition' => $segmentDefinition]);
        $form = $this->factory->create(ProductCollectionSegmentType::class, $segment, ['add_name_field' => true]);

        $this->assertTrue($form->has('name'));
        $options = $form->get('name')->getConfig()->getOptions();
        $this->assertSame(true, $options['required']);
        $this->assertEquals([new NotBlank()], $options['constraints']);
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductCollectionSegmentType::NAME, $this->type->getBlockPrefix());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create(ProductCollectionSegmentType::class, null);

        $options = $form->getConfig()->getOptions();

        $this->assertSame('product-collection-grid', $options['results_grid']);
        $this->assertSame('product-collection-excluded-products-grid', $options['excluded_products_grid']);
        $this->assertSame('product-collection-included-products-grid', $options['included_products_grid']);
    }

    public function testIncludedAndExcludedFieldsSet()
    {
        $segmentDefinition
            = '{"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}';
        $segment = new Segment();
        $segment->setDefinition($segmentDefinition);

        $includedProductsString = '1,3,7';
        $excludedProductsString = '17';

        $this->definitionConverter
            ->expects($this->any())
            ->method('getDefinitionParts')
            ->with($segmentDefinition)
            ->willReturn([
                ProductCollectionDefinitionConverter::DEFINITION_KEY => '{}',
                ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => $includedProductsString,
                ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY => $excludedProductsString
            ]);

        $form = $this->factory->create(ProductCollectionSegmentType::class, $segment);

        $this->assertEquals($includedProductsString, $form->get('includedProducts')->getData());
        $this->assertEquals($excludedProductsString, $form->get('excludedProducts')->getData());
    }

    public function testIncludedAndExcludedFieldsGet()
    {
        $segmentDefinition
            = '{"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}';
        $segment = new Segment();
        $segment->setDefinition($segmentDefinition);

        $includedProductsString = '1,3,7';
        $excludedProductsString = '17';
        $modifiedDefinition = sprintf(
            '{%s,%s}',
            '"filters":[{"columnName":"sku","criterion":{"filter":"string"}}]',
            '"columns":[{"name":"sku","label":"sku","sorting":null,"func":null}]'
        );

        $this->definitionConverter->expects($this->any())
            ->method('getDefinitionParts')
            ->with($segmentDefinition)
            ->willReturn(
                [
                    ProductCollectionDefinitionConverter::DEFINITION_KEY => $segmentDefinition,
                    ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY => $excludedProductsString,
                    ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => $includedProductsString
                ]
            );
        $this->definitionConverter
            ->expects($this->any())
            ->method('putConditionsInDefinition')
            ->with(
                $this->isType('string'),
                $excludedProductsString,
                $includedProductsString
            )
            ->willReturn($modifiedDefinition);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityReference')
            ->willReturnMap([
                [SegmentType::class, SegmentType::TYPE_DYNAMIC, new SegmentType(SegmentType::TYPE_DYNAMIC)],
            ]);

        $user = new User();
        $user->setOwner(new BusinessUnit());
        $user->setOrganization(new Organization());
        $token = $this->createMock(TokenInterface::class);
        $token
            ->expects($this->any())
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $form = $this->factory->create(ProductCollectionSegmentType::class, $segment, ['segment_columns' => ['sku']]);
        $form->submit([
            'name' => 'Product Collection Name',
            'entity' => Product::class,
            'definition' => '{}',
            'includedProducts' => $includedProductsString,
            'excludedProducts' => $excludedProductsString
        ]);

        /** @var Segment $segment */
        $segment = $form->getData();

        $this->assertEquals($modifiedDefinition, $segment->getDefinition());
    }

    public function testFormView()
    {
        $segmentDefinition
            = '{"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}';
        $segment = new Segment();
        $segment->setDefinition($segmentDefinition);

        $includedProducts = '1,5';
        $excludedProducts = '2,11';

        $modifiedDefinition = '{}';
        $this->definitionConverter
            ->expects($this->any())
            ->method('getDefinitionParts')
            ->with($segmentDefinition)
            ->willReturn([
                ProductCollectionDefinitionConverter::DEFINITION_KEY => $modifiedDefinition,
                ProductCollectionDefinitionConverter::INCLUDED_FILTER_KEY => $includedProducts,
                ProductCollectionDefinitionConverter::EXCLUDED_FILTER_KEY => $excludedProducts
            ]);

        $form = $this->factory->create(ProductCollectionSegmentType::class, $segment);
        $formView = $form->createView();

        $this->assertEquals($modifiedDefinition, $formView->children['definition']->vars['value']);
        $this->assertEquals($includedProducts, $formView->children['includedProducts']->vars['value']);
        $this->assertEquals($excludedProducts, $formView->children['excludedProducts']->vars['value']);
    }

    public function testFinishView()
    {
        $form = $this->factory->create(ProductCollectionSegmentType::class);

        $view = $form->createView();
        $segmentDefinition = '
            {"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}
        ';
        $segmentDefinitionFieldName = 'segment-definition-field-name';
        $options = [
            'results_grid' => 'test',
            'included_products_grid' => 'included_grid',
            'excluded_products_grid' => 'excluded_grid',
            'add_name_field' => true,
            'scope_value' => 'productCollectionSegment'
        ];

        $hasFilters = true;
        $this->definitionConverter
            ->expects($this->any())
            ->method('hasFilters')
            ->with($segmentDefinition)
            ->willReturn($hasFilters);
        $view->children['definition']->vars['full_name'] = $segmentDefinitionFieldName;
        $view->children['definition']->vars['value'] = $segmentDefinition;

        $this->type->finishView($view, $form, $options);

        $this->assertSame('test', $view->vars['results_grid']);
        $this->assertSame('included_grid', $view->vars['includedProductsGrid']);
        $this->assertSame('excluded_grid', $view->vars['excludedProductsGrid']);
        $this->assertSame($segmentDefinition, $view->vars['segmentDefinition']);
        $this->assertSame($segmentDefinitionFieldName, $view->vars['segmentDefinitionFieldName']);
        $this->assertSame($hasFilters, $view->vars['hasFilters']);
        $this->assertSame(true, $view->vars['addNameField']);
        $this->assertSame('productCollectionSegment', $view->vars['scopeValue']);
    }
}
