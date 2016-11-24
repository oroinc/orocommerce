<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\ContentNodeUtils\ContentNodeNameFiller;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentVariantCollectionType;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\PreloadedExtension;

class ContentNodeTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var ContentNodeType
     */
    protected $type;

    /**
     * @var ContentNodeNameFiller|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $nameFiller;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->nameFiller = $this->getMockBuilder(ContentNodeNameFiller::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new ContentNodeType($this->nameFiller);
    }

    /**
     * {@inheritDoc}
     */
    protected function tearDown()
    {
        unset($this->type);
    }

    /**
     * @return array
     */
    protected function getExtensions()
    {
        $variantTypeRegistry = $this->getMockBuilder(ContentVariantTypeRegistry::class)
            ->disableOriginalConstructor()
            ->getMock();
        $variantTypeRegistry->expects($this->any())
            ->method('getFormTypeByType')
            ->with('system_page')
            ->willReturn(SystemPageVariantType::class);
        $variantTypeRegistry->expects($this->any())
            ->method('getAllowedContentVariantTypes')
            ->willReturn([]);

        $variantCollection = new ContentVariantCollectionType($variantTypeRegistry);

        return [
            new PreloadedExtension(
                [
                    TextType::class => new TextType(),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType([]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    ContentVariantCollectionType::NAME => $variantCollection,
                    RouteChoiceType::NAME => new RouteChoiceTypeStub(
                        [
                            'some_route' => 'some_route',
                            'other_route' => 'other_route'
                        ]
                    ),
                    LocalizedSlugType::NAME => new LocalizedSlugTypeStub(),
                ],
                []
            ),
            $this->getValidatorExtension(true)
        ];
    }

    public function testBuildFormRootEntity()
    {
        $form = $this->factory->create($this->type, new ContentNode());

        $this->assertTrue($form->has('parentNode'));
        $this->assertTrue($form->has('titles'));
        $this->assertFalse($form->has('slugPrototypes'));
    }

    public function testBuildFormSubNode()
    {
        $node = new ContentNode();
        $node->setParentNode(new ContentNode());
        $form = $this->factory->create($this->type, $node);

        $this->assertTrue($form->has('parentNode'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('slugPrototypes'));
        $this->assertTrue($form->has('scopes'));
    }

    public function testBuildFormForExistingEntity()
    {
        $node = $this->getEntity(ContentNode::class, ['id' => 1]);
        $form = $this->factory->create($this->type, $node);

        $this->assertTrue($form->has('parentNode'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('scopes'));
    }

    public function testGetName()
    {
        $this->assertEquals(ContentNodeType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ContentNodeType::NAME, $this->type->getBlockPrefix());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentNode $existingData
     * @param array $submittedData
     * @param ContentNode $expectedData
     */
    public function testSubmit($existingData, $submittedData, $expectedData)
    {
        $this->nameFiller->expects($this->once())
            ->method('fillName')
            ->with($existingData)
            ->willReturnCallback(
                function (ContentNode $data) {
                    $data->setName('filled_name');
                }
            );

        $form = $this->factory->create($this->type, $existingData);

        $this->assertEquals($existingData, $form->getData());

        $form->submit($submittedData);
        $errors = array_map(
            function (FormError $error) {
                return $error->getMessage();
            },
            iterator_to_array($form->getErrors())
        );
        $this->assertEquals([], $errors);
        $this->assertTrue($form->isValid());

        /** @var ContentNode $data */
        $data = $form->getData();
        $this->assertInstanceOf(ContentNode::class, $data);
        $this->assertEquals($expectedData, $data);
        foreach ($data->getContentVariants() as $contentVariant) {
            $this->assertEquals($data, $contentVariant->getNode());
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new entity' => [
                (new ContentNode())
                    ->setParentNode(new ContentNode()),
                [
                    'titles' => [['string' => 'new_content_node_title']],
                    'slugPrototypes' => [['string' => 'new_content_node_slug']],
                    'parentScopeUsed' => true
                ],
                (new ContentNode())
                    ->setName('filled_name')
                    ->addTitle((new LocalizedFallbackValue())->setString('new_content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('new_content_node_slug'))
                    ->setParentScopeUsed(true),
            ],
            'existing entity' => [
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug')),
                [
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                    'parentScopeUsed' => false
                ],
                (new ContentNode())
                    ->setName('filled_name')
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('another_node_slug'))
                    ->setParentScopeUsed(false),
            ],
            'added variant' => [
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug')),
                [
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => [],
                        ]
                    ],
                    'parentScopeUsed' => true
                ],
                (new ContentNode())
                    ->setName('filled_name')
                    ->setParentScopeUsed(true)
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('another_node_slug'))
                    ->addContentVariant(
                        (new ContentVariant())
                            ->setType('system_page')
                            ->setSystemPageRoute('some_route')
                    )
            ],
            'remove variant' => [
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addContentVariant(
                        (new ContentVariant())->setType('system_page')->setSystemPageRoute('some_route')
                    )
                    ->addContentVariant(
                        (new ContentVariant())->setType('system_page')->setSystemPageRoute('other_route')
                    ),
                [
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => []
                        ]
                    ]
                ],
                (new ContentNode())
                    ->setName('filled_name')
                    ->setParentScopeUsed(false)
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('another_node_slug'))
                    ->addContentVariant(
                        (new ContentVariant())
                            ->setType('system_page')
                            ->setSystemPageRoute('some_route')
                    )
            ],
        ];
    }
}
