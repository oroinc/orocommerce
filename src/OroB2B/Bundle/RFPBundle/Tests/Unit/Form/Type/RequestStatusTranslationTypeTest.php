<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPBundle\Form\Type\RequestStatusTranslationType;

class RequestStatusTranslationTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestStatusTranslationType
     */
    protected $type;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->type = new RequestStatusTranslationType();
    }

    /**
     * Test getName
     */
    public function testGetName()
    {
        $this->assertEquals(RequestStatusTranslationType::NAME, $this->type->getName());
    }

    /**
     * Test getParent
     */
    public function testGetParent()
    {
        $this->assertEquals('a2lix_translations_gedmo', $this->type->getParent());
    }

    /**
     * Test setDefaultOptions
     */
    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolverInterface $optionsResolver */
        $optionsResolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolverInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $optionsResolver->expects($this->once())
            ->method('setDefaults');

        $this->type->setDefaultOptions($optionsResolver);
    }

    public function testBuildView()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|Form $form */
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $view = new FormView();

        $this->assertArrayNotHasKey('labels', $view->vars);

        $options = ['labels' => ['first', 'second']];
        $this->type->buildView($view, $form, $options);

        $this->assertArrayHasKey('labels', $view->vars);
        $this->assertEquals($options['labels'], $view->vars['labels']);
    }
}
