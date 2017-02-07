<?php

namespace Oro\Bundle\FrontendBundle\Tests\Unit\Form\Type;

use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateCollectionType;
use Oro\Bundle\FrontendBundle\Form\Type\PageTemplateType;
use Oro\Component\Layout\Extension\Theme\Manager\PageTemplatesManager;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class PageTemplateTypeTest extends FormIntegrationTestCase
{
    /** @var PageTemplatesManager|\PHPUnit_Framework_MockObject_MockObject */
    private $pageTemplatesManagerMock;

    /** @var PageTemplateCollectionType */
    private $formType;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();

        $this->pageTemplatesManagerMock = $this->getMockBuilder(PageTemplatesManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->formType = new PageTemplateType($this->pageTemplatesManagerMock);
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

        $form = $this->factory->create($this->formType, null, ['route_name' => 'route_name_1']);
        $submittedData = 'some_key2';

        $form->submit($submittedData);
        $this->assertTrue($form->isValid());

        $formData = $form->getData();
        $this->assertEquals('some_key2', $formData);
    }
}
