<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\OrganizationBundle\Entity\BusinessUnit;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter;
use Oro\Bundle\ProductBundle\Tests\Unit\ContentVariant\Stub\ContentVariantStub;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ProductCollectionVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var EntityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $entityProvider;

    /**
     * @var Manager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $queryDesignerManager;

    /**
     * @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private $doctrineHelper;

    /**
     * @var TokenStorageInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $tokenStorage;

    /**
     * @var ProductCollectionDefinitionConverter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $definitionConverter;

    /**
     * @var ProductCollectionVariantType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->entityProvider = $this->createMock(EntityProvider::class);
        $this->queryDesignerManager = $this->createMock(Manager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->definitionConverter = $this->createMock(ProductCollectionDefinitionConverter::class);

        parent::setUp();
        $this->type = new ProductCollectionVariantType($this->definitionConverter);
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

        $configProvider = $this->createMock(ConfigProvider::class);
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    SegmentFilterBuilderType::NAME => $segmentFilterBuilderType
                ],
                [
                    'form' => [new TooltipFormExtension($configProvider, $translator)],
                ]
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type, null);
        $this->assertTrue($form->has('productCollectionSegment'));
        $this->assertEquals(
            ProductCollectionContentVariantType::TYPE,
            $form->getConfig()->getOption('content_variant_type')
        );
        $this->assertEquals('product-collection-grid', $form->getConfig()->getOption('results_grid'));
    }

    public function testGetName()
    {
        $this->assertEquals(ProductCollectionVariantType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ProductCollectionVariantType::NAME, $this->type->getBlockPrefix());
    }

    public function testDefaultOptions()
    {
        $form = $this->factory->create($this->type, null);

        $expectedDefaultOptions = [
            'results_grid' => 'product-collection-grid',
            'excluded_products_grid' => 'product-collection-excluded-products-grid',
            'included_products_grid' => 'product-collection-included-products-grid'
        ];

        $this->assertArraySubset($expectedDefaultOptions, $form->getConfig()->getOptions());
    }

    public function testIncludedAndExcludedFieldsSet()
    {
        $segmentDefinition
            = '{"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}';
        $segment = new Segment();
        $segment->setDefinition($segmentDefinition);

        $data = new ContentVariantStub();
        $data->setProductCollectionSegment($segment);

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

        $form = $this->factory->create($this->type, $data);

        $this->assertEquals($includedProductsString, $form->get('includedProducts')->getData());
        $this->assertEquals($excludedProductsString, $form->get('excludedProducts')->getData());
    }

    public function testIncludedAndExcludedFieldsGet()
    {
        $segmentDefinition
            = '{"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}';
        $segment = new Segment();
        $segment->setDefinition($segmentDefinition);

        $data = new ContentVariantStub();
        $data->setProductCollectionSegment($segment);

        $includedProductsString = '1,3,7';
        $excludedProductsString = '17';
        $modifiedDefinition = '{}';

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

        $form = $this->factory->create($this->type, $data);
        $form->submit([
            'productCollectionSegment' => [
                'name' => 'Product Collection Name',
                'entity' => Product::class,
                'definition' => '{}'
            ],
            'includedProducts' => $includedProductsString,
            'excludedProducts' => $excludedProductsString
        ]);

        /** @var Segment $segment */
        $segment = $form->get('productCollectionSegment')->getData();

        $this->assertEquals($modifiedDefinition, $segment->getDefinition());
    }

    public function testFinishView()
    {
        $view = new FormView();
        $segmentDefinition = '
            {"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}
        ';
        $segmentDefinitionFieldName = 'segment-definition-field-name';
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $options = [
            'results_grid' => 'test',
            'included_products_grid' => 'included_grid',
            'excluded_products_grid' => 'excluded_grid',
        ];
        $view->children['productCollectionSegment']
            ->children['definition']->vars['full_name'] = $segmentDefinitionFieldName;
        $view->children['productCollectionSegment']
            ->children['definition']->vars['value'] = $segmentDefinition;

        $this->type->finishView($view, $form, $options);
        $this->assertEquals('test', $view->vars['results_grid']);
        $this->assertEquals('included_grid', $view->vars['includedProductsGrid']);
        $this->assertEquals('excluded_grid', $view->vars['excludedProductsGrid']);
        $this->assertEquals($segmentDefinition, $view->vars['segmentDefinition']);
        $this->assertEquals($segmentDefinitionFieldName, $view->vars['segmentDefinitionFieldName']);
    }
}
