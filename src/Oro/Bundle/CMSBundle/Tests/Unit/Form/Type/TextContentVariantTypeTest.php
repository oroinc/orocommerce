<?php

namespace Oro\Bundle\CMSBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FormBundle\Form\Type\OroRichTextType;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\TextContentVariant;
use Oro\Bundle\CMSBundle\Form\Type\TextContentVariantType;
use Oro\Bundle\CMSBundle\Form\Type\ContentBlockType;
use Oro\Bundle\ScopeBundle\Form\Type\ScopeCollectionType;
use Oro\Bundle\ScopeBundle\Tests\Unit\Form\Type\Stub\ScopeCollectionTypeStub;

use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use Symfony\Component\Form\PreloadedExtension;

class TextContentVariantTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ContentBlockType
     */
    protected $type;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->type = new TextContentVariantType();
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
        /**
         * @var \Oro\Bundle\ConfigBundle\Config\ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager
         */
        $configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $htmlTagProvider = $this->createMock('Oro\Bundle\FormBundle\Provider\HtmlTagProvider');
        $htmlTagProvider->expects($this->any())
            ->method('getAllowedElements')
            ->willReturn(['br', 'a']);

        return [
            new PreloadedExtension(
                [
                    ScopeCollectionType::NAME => new ScopeCollectionTypeStub(),
                    OroRichTextType::NAME => new OroRichTextType($configManager, $htmlTagProvider),
                ],
                []
            )
        ];
    }

    public function testBuildForm()
    {
        $form = $this->factory->create($this->type);

        $this->assertTrue($form->has('scopes'));
        $this->assertTrue($form->has('content'));
        $this->assertTrue($form->has('default'));
    }

    /**
     * @dataProvider submitDataProvider
     *
     * @param ContentBlock $existingData
     * @param array $submittedData
     * @param ContentBlock $expectedData
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
                new TextContentVariant(),
                [
                    'scopes' => [],
                    'content' => 'some_title',
                ],
                (new TextContentVariant())
                    ->setContent('some_title')
            ],
            'existing entity' => [
                (new TextContentVariant())
                    ->setContent('some_old_title')
                    ->setContentBlock(new ContentBlock()),
                [
                    'scopes' => [],
                    'content' => 'some_changed_title',
                    'default' => true
                ],
                (new TextContentVariant())
                    ->setContent('some_changed_title')
                    ->setContentBlock(new ContentBlock())
                    ->setDefault(true)
            ],
        ];
    }
}
