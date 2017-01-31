<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;
use Oro\Bundle\NavigationBundle\Form\Type\RouteChoiceType;
use Oro\Bundle\NavigationBundle\Tests\Unit\Form\Type\Stub\RouteChoiceTypeStub;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugWithRedirectType;
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
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new ContentNodeType();
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
        /** @var ContentVariantTypeRegistry|\PHPUnit_Framework_MockObject_MockObject $variantTypeRegistry */
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

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        return [
            new PreloadedExtension(
                [
                    TextType::class => new TextType(),
                    EntityIdentifierType::NAME => new StubEntityIdentifierType(
                        [
                            1 => $this->getEntity(ContentNode::class, ['id' => 1])
                        ]
                    ),
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
                    LocalizedSlugWithRedirectType::NAME => new LocalizedSlugWithRedirectType($configManager),
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
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('contentVariants'));
        $this->assertFalse($form->has('parentScopeUsed'));
        $this->assertFalse($form->has('slugPrototypesWithRedirect'));
    }

    public function testBuildFormSubNode()
    {
        $node = new ContentNode();
        $node->setParentNode(new ContentNode());
        $form = $this->factory->create($this->type, $node);

        $this->assertTrue($form->has('parentNode'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('contentVariants'));
        $this->assertTrue($form->has('parentScopeUsed'));
        $this->assertTrue($form->has('slugPrototypesWithRedirect'));
    }

    public function testBuildFormForExistingEntity()
    {
        $node = $this->getEntity(ContentNode::class, ['id' => 1]);
        $form = $this->factory->create($this->type, $node);

        $this->assertTrue($form->has('parentNode'));
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
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']]
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
                    'parentNode' => 1,
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugPrototypesWithRedirect' => [
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']]
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
                        'slugPrototypes' => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']]
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
