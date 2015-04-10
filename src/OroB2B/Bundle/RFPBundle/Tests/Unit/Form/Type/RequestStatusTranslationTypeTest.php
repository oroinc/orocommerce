<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormView;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusTranslationType;

class RequestStatusTranslationTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStatusTranslationType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new RequestStatusTranslationType();
    }

    public function testGetName()
    {
        $this->assertEquals(RequestStatusTranslationType::NAME, $this->type->getName());
    }

    public function testGetParent()
    {
        $this->assertEquals('a2lix_translations_gedmo', $this->type->getParent());
    }

    public function testSetDefaultOptions()
    {
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $optionsResolver->expects($this->once())
            ->method('setDefaults');

        $this->type->setDefaultOptions($optionsResolver);
    }

    public function testBuildView()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $view = new FormView();

        $this->assertArrayNotHasKey('labels', $view->vars);

        $options = ['labels' => ['first', 'second']];
        $this->type->buildView($view, $form, $options);

        $this->assertArrayHasKey('labels', $view->vars);
        $this->assertEquals($options['labels'], $view->vars['labels']);
    }
}
