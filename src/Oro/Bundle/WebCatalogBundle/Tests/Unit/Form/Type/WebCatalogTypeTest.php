<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Unit\Form\Type;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\FormBundle\Provider\HtmlTagProvider;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;
use Oro\Bundle\WebCatalogBundle\Form\Type\WebCatalogType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\Form\Test\FormIntegrationTestCase;

class WebCatalogTypeTest extends FormIntegrationTestCase
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

        $this->type = new WebCatalogType();
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
        $configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider = $this->getMockBuilder(HtmlTagProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);
        $richTextType = new OroRichTextType($configManager, $htmlTagProvider);

        return [
            new PreloadedExtension(
                [

                    TextType::class => new TextType(),
                    OroRichTextType::class => $richTextType
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
    }

    public function testGetName()
    {
        $this->assertEquals(WebCatalogType::NAME, $this->type->getName());
    }

    public function testGetBlockPrefix()
    {
        $this->assertEquals(WebCatalogType::NAME, $this->type->getName());
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param WebCatalog $existingData
     * @param array $submittedData
     * @param WebCatalog $expectedData
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
            'new entity' => [
                new WebCatalog(),
                [
                    'name' => 'name',
                    'description' => 'description'
                ],
                (new WebCatalog())
                    ->setName('name')
                    ->setDescription('description')
            ],
            'existing entity' => [
                (new WebCatalog())
                    ->setName('name')
                    ->setDescription('description'),
                [
                    'name' => 'name UP',
                    'description' => 'description UP'
                ],
                (new WebCatalog())
                    ->setName('name UP')
                    ->setDescription('description UP')
            ],
        ];
    }
}
