<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\ProductBundle\Form\Extension\IntegerExtension;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegerExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const TYPE = 'text';

    /** @var IntegerExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new IntegerExtension();
    }

    public function testConfigureOptions()
    {
        $resolver = $this->createMock(OptionsResolver::class);
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
        $form = $this->createMock(Form::class);

        $this->extension->finishView($view, $form, $options);

        if (!empty($view->vars['type'])) {
            $this->assertSame($view->vars['type'], self::TYPE);
        } else {
            $this->assertArrayNotHasKey('type', $view->vars);
        }
    }

    public function finishViewData(): array
    {
        return [
            'with type' => [
                'view'   => $this->createView(self::TYPE),
                'option' => ['type' => self::TYPE]
            ],
            'without type' => [
                'view'   => $this->createView(),
                'option' => ['type' => null]
            ]
        ];
    }

    private function createView(string $type = null): FormView
    {
        $result = new FormView();
        if ($type) {
            $result->vars = ['type' => $type];
        }

        return $result;
    }
}
