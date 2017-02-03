<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateCollectionType;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class PageTemplateCollectionTypeTest extends FormIntegrationTestCase
{
    /** @var PageTemplatesManager|\PHPUnit_Framework_MockObject_MockObject */
    private $pageTemplatesManagerMock;

    /** @var PageTemplateCollectionType */
    private $formType;

    protected function setUp()
    {
        parent::setUp();
        $this->pageTemplatesManagerMock = $this->getMockBuilder(PageTemplatesManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new PageTemplateCollectionType($this->pageTemplatesManagerMock);
    }

    public function testGetName()
    {
        $this->assertEquals(PageTemplateCollectionType::NAME, $this->formType->getName());
    }

    public function testSubmit()
    {
        $this->pageTemplatesManagerMock->expects($this->once())
            ->method('getRoutePageTemplates')
            ->willReturn([
                'route_name_1' => [
                    'label' => 'Route title 1',
                    'choices' => [
                        'some_key1' => 'Page template 1',
                        'some_key2' => 'Page template 2'
                    ]
                ]
            ]);

        $form = $this->factory->create($this->formType, []);
        $submittedData = ['route_name_1' => 'some_key2'];

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $formData = $form->getData();
        $this->assertEquals(['route_name_1' => 'some_key2'], $formData);
    }
}
