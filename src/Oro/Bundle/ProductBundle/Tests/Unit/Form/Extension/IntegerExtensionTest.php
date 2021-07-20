<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerExtensionTest extends \PHPUnit\Framework\TestCase
{
    const TYPE = 'text';

    /**
     * @var IntegerExtension
     */
    protected $extension;

    protected function setUp(): void
    {
        $this->extension = new IntegerExtension();
    }

    public function testConfigureOptions()
    {
        /** @var OptionsResolver|\PHPUnit\Framework\MockObject\MockObject $resolver */
        $resolver = $this->createMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(['type' => null]);

        $this->extension->configureOptions($resolver);
    }

    public function testGetExtendedTypes()
    {
        $this->assertEquals([IntegerType::class], IntegerExtension::getExtendedTypes());
    }

    /**
     * @dataProvider finishViewData
     */
    public function testFinishView(FormView $view, array $options)
    {
        /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
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
