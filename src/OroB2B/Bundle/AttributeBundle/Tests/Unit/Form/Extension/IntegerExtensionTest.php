<?php

namespace OroB2B\Bundle\AttributeBundle\Tests\Unit\Form\Extension;

use OroB2B\Bundle\AttributeBundle\Form\Extension\IntegerExtension;
use Symfony\Component\Form\FormView;

class IntegerExtensionTest extends \PHPUnit_Framework_TestCase
{
    const TYPE = 'text';

    /**
     * @var IntegerExtension
     */
    protected $extension;

    protected function setUp()
    {
        $this->extension = new IntegerExtension();
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['type' => null]);

        $this->extension->setDefaultOptions($resolver);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals('integer', $this->extension->getExtendedType());
    }

    /**
     * @dataProvider finishViewData
     * @param FormView $view
     * @param array $options
     */
    public function testFinishView(FormView $view, array $options)
    {
        $form = $this->getMockBuilder('Symfony\Component\Form\Form')
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension->finishView($view, $form, $options);

        if (!empty($view->vars['type'])) {
            $this->assertTrue($view->vars['type'] === self::TYPE);
        } else {
            $this->assertArrayNotHasKey('type', $view->vars);
        }
    }

    public function finishViewData()
    {
        return array(
            'with type' => array(
                'view'   => $this->createView(self::TYPE),
                'option' => array('type' => self::TYPE)
            ),
            'without type' => array(
                'view'   => $this->createView(),
                'option' => array('type' => null)
            )
        );
    }

    protected function createView($type = null)
    {
        $result = new FormView();
        if ($type) {
            $result->vars = array('type' => $type);
        }

        return $result;
    }
}
