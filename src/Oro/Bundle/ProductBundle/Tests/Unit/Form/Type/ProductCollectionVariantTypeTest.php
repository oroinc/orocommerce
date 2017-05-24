<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\FormBundle\Form\Extension\TooltipFormExtension;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Type\ProductCollectionVariantType;
use Oro\Bundle\QueryDesignerBundle\QueryDesigner\Manager;
use Oro\Bundle\SegmentBundle\Form\Type\SegmentFilterBuilderType;
use Oro\Bundle\TranslationBundle\Translation\Translator;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

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

        parent::setUp();
        $this->type = new ProductCollectionVariantType();
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
            $this->getValidatorExtension(true)
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

    public function testFinishView()
    {
        $view = new FormView();
        $segmentDefinition = '
            {"filters":[{"columnName":"id","criterion":{"filter":"number","data":{"value":3,"type":"3"}}}]}
        ';
        $segmentDefinitionFieldName = 'segment-definition-field-name';
        /** @var FormInterface $form */
        $form = $this->createMock(FormInterface::class);
        $options = ['results_grid' => 'test'];
        $view->children['productCollectionSegment']
            ->children['definition']->vars['full_name'] = $segmentDefinitionFieldName;
        $view->children['productCollectionSegment']
            ->children['definition']->vars['value'] = $segmentDefinition;

        $this->type->finishView($view, $form, $options);
        $this->assertEquals('test', $view->vars['results_grid']);
        $this->assertEquals($segmentDefinition, $view->vars['segmentDefinition']);
        $this->assertEquals($segmentDefinitionFieldName, $view->vars['segmentDefinitionFieldName']);
    }
}
