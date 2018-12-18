<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
use Oro\Bundle\RedirectBundle\Helper\ConfirmSlugChangeFormHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Oro\Bundle\WebCatalogBundle\ContentVariantType\ContentVariantTypeRegistry;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentVariantCollectionType;
use Oro\Bundle\WebCatalogBundle\Form\Type\SystemPageVariantType;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\PreloadedExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Routing\RouterInterface;

class ContentNodeTypeTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /**
     * @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $router;

    /**
     * @var ContentNodeType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->router = $this->createMock(RouterInterface::class);

        $this->type = new ContentNodeType($this->router);
        parent::setUp();
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
        /** @var ContentVariantTypeRegistry|\PHPUnit\Framework\MockObject\MockObject $variantTypeRegistry */
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

        /** @var ConfirmSlugChangeFormHelper $confirmSlugChangeFormHelper */
        $confirmSlugChangeFormHelper = $this->getMockBuilder(ConfirmSlugChangeFormHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    ContentNodeType::class => $this->type,
                    TextType::class => new TextType(),
                    LocalizedFallbackValueCollectionType::class => new LocalizedFallbackValueCollectionTypeStub(),
                    ScopeCollectionType::class => new ScopeCollectionTypeStub(),
                    ContentVariantCollectionType::class => $variantCollection,
                    RouteChoiceType::class => new RouteChoiceTypeStub(
                        [
                            'some_route' => 'some_route',
                            'other_route' => 'other_route'
                        ]
                    ),
                    LocalizedSlugType::class => new LocalizedSlugTypeStub(),
                    LocalizedSlugWithRedirectType::class
                        => new LocalizedSlugWithRedirectType($confirmSlugChangeFormHelper),
                ],
                []
            ),
            $this->getValidatorExtension(false)
        ];
    }

    public function testBuildFormRootEntity()
    {
        $form = $this->factory->create(ContentNodeType::class, new ContentNode());

        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('contentVariants'));
        $this->assertFalse($form->has('parentScopeUsed'));
        $this->assertFalse($form->has('slugPrototypesWithRedirect'));
    }

    public function testBuildFormSubNode()
    {
        $node = new ContentNode();
        $node->setParentNode(new ContentNode());
        $form = $this->factory->create(ContentNodeType::class, $node);

        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('contentVariants'));
        $this->assertTrue($form->has('parentScopeUsed'));
        $this->assertTrue($form->has('slugPrototypesWithRedirect'));
    }

    public function testBuildFormForExistingEntity()
    {
        $node = $this->getEntity(ContentNode::class, ['id' => 1]);
        $form = $this->factory->create(ContentNodeType::class, $node);

        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('scopes'));
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
        $form = $this->factory->create(ContentNodeType::class, $existingData);

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

    public function testGenerateChangedSlugsUrlOnPresetData()
    {
        $generatedUrl = '/some/url';
        $this->router
            ->expects($this->once())
            ->method('generate')
            ->with('oro_content_node_get_changed_urls', ['id' => 1])
            ->willReturn($generatedUrl);

        /** @var ContentNode $existingData */
        $existingData = $this->getEntity(ContentNode::class, [
            'id' => 1,
            'slugPrototypes' => new ArrayCollection([$this->getEntity(LocalizedFallbackValue::class)])
        ]);
        $existingData->setParentNode(new ContentNode());

        /** @var Form $form */
        $form = $this->factory->create(ContentNodeType::class, $existingData);

        $formView = $form->createView();

        $this->assertArrayHasKey('slugPrototypesWithRedirect', $formView->children);
        $this->assertEquals(
            $generatedUrl,
            $formView->children['slugPrototypesWithRedirect']
                ->vars['confirm_slug_change_component_options']['changedSlugsUrl']
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new root entity' => [
                new ContentNode(),
                [
                    'titles' => [['string' => 'new_content_node_title']],
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => [],
                        ]
                    ],
                ],
                (new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('new_content_node_title'))
                    ->setParentScopeUsed(false)
                    ->setRewriteVariantTitle(false)
                    ->addContentVariant(
                        (new ContentVariant())
                            ->setType('system_page')
                            ->setSystemPageRoute('some_route')
                    ),
            ],
            'existing entity' => [
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug')),
                [
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                        'createRedirect' => true,
                    ],
                    'parentScopeUsed' => false,
                    'rewriteVariantTitle' => true,
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => [],
                        ]
                    ],
                ],
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('another_node_slug'))
                    ->setParentScopeUsed(false)
                    ->addContentVariant(
                        (new ContentVariant())
                            ->setType('system_page')
                            ->setSystemPageRoute('some_route')
                    ),
            ],
            'added variant' => [
                (new ContentNode())
                    ->setParentNode($this->getEntity(ContentNode::class, ['id' => 1]))
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug')),
                [
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                        'createRedirect' => true,
                    ],
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => [],
                        ]
                    ],
                    'parentScopeUsed' => true,
                    'rewriteVariantTitle' => true
                ],
                (new ContentNode())
                    ->setParentNode($this->getEntity(ContentNode::class, ['id' => 1]))
                    ->setParentScopeUsed(true)
                    ->setRewriteVariantTitle(true)
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
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                        'createRedirect' => true,
                    ],
                    'contentVariants' => [
                        [
                            'type' => 'system_page',
                            'systemPageRoute' => 'some_route',
                            'scopes' => []
                        ]
                    ],
                    'rewriteVariantTitle' => false
                ],
                (new ContentNode())
                    ->setParentNode(new ContentNode())
                    ->setParentScopeUsed(false)
                    ->setRewriteVariantTitle(false)
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
