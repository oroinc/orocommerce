<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\EntityIdentifierType;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Form\Type\ContentNodeType;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
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
                ],
                []
            ),
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('parentNode'));
        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('titles'));
        $this->assertTrue($form->has('slugs'));
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
                new ContentNode(),
                [
                    'name'   => 'name',
                    'titles' => [['string' => 'new_content_node_title']],
                    'slugs'  => [['string' => 'new_content_node_slug']],
                ],
                (new ContentNode())
                    ->setName('name')
                    ->addTitle((new LocalizedFallbackValue())->setString('new_content_node_title'))
                    ->addSlug((new LocalizedFallbackValue())->setString('new_content_node_slug')),
            ],
            'existing entity' => [
                (new ContentNode())
                    ->setName('name')
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addSlug((new LocalizedFallbackValue())->setString('content_node_slug')),
                [
                    'name'   => 'name UP',
                    'titles' => [['string' => 'content_node_title'], ['string' => 'another_node_title']],
                    'slugs'  => [['string' => 'content_node_slug'], ['string' => 'another_node_slug']],
                ],
                (new ContentNode())
                    ->setName('name UP')
                    ->addTitle((new LocalizedFallbackValue())->setString('content_node_title'))
                    ->addTitle((new LocalizedFallbackValue())->setString('another_node_title'))
                    ->addSlug((new LocalizedFallbackValue())->setString('content_node_slug'))
                    ->addSlug((new LocalizedFallbackValue())->setString('another_node_slug')),
            ],
        ];
    }
}
