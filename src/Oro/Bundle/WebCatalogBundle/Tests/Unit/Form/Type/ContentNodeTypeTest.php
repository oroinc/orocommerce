<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\RedirectBundle\Form\Type\LocalizedSlugType;
use Oro\Bundle\RedirectBundle\Tests\Unit\Form\Type\Stub\LocalizedSlugTypeStub;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;
use Oro\Component\Testing\Unit\Form\Type\Stub\EntityIdentifierType as StubEntityIdentifierType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\LocaleBundle\Tests\Unit\Form\Type\Stub\LocalizedFallbackValueCollectionTypeStub;

class ContentNodeTypeTest extends FormIntegrationTestCase
{
    /**
     * @var WebCatalogType
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
        return [
            new PreloadedExtension(
                [
                    TextType::class                            => new TextType(),
                    EntityIdentifierType::NAME                 => new StubEntityIdentifierType([]),
                    LocalizedFallbackValueCollectionType::NAME => new LocalizedFallbackValueCollectionTypeStub(),
                    LocalizedSlugType::NAME                    => new LocalizedSlugTypeStub(),
                    ScopeCollectionType::NAME                  => new ScopeCollectionTypeStub()
                ],
                []
            ),
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

    public function testGetName()
    {
        $this->assertEquals(ContentNodeType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(ContentNodeType::NAME, $this->type->getName());
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
        $this->assertTrue($form->isValid());

        $this->assertEquals($expectedData, $form->getData());
    }

    /**
     * @return array
     */
    public function submitDataProvider()
    {
        return [
            'new entity'      => [
                (new ContentNode())
                    ->setParentNode(new ContentNode()),
                [
                    'titles' => [['string' => 'new_content_node_title']],
                    'slugPrototypes'  => [['string' => 'new_content_node_slug']],
                    'parentScopeUsed' => true
                ],
                (new ContentNode())
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
                    'slugPrototypes'  => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                    'parentScopeUsed' => false
                ],
                (new ContentNode())
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlugPrototype((new LocalizedFallbackValue())->setString('another_node_slug'))
                    ->setParentScopeUsed(false),
            ],
        ];
    }
}
