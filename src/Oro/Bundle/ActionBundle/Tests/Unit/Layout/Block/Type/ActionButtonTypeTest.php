<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Layout\Block\Type;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Layout\Block\Type\ActionButtonType;
use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\BlockView;

class ActionButtonTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var  ActionButtonType */
    public $blockType;

    public function setUp()
    {
        /** @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $translator->expects($this->any())->method('trans')->will($this->returnArgument(0));
        $this->blockType = new ActionButtonType($translator);
        parent::setUp();
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $options
     * @param array $expectedVars
     */
    public function testBuildView(array $options, array $expectedVars)
    {
        $blockView = new BlockView();
        /** @var BlockInterface|\PHPUnit_Framework_MockObject_MockObject $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');
        $this->blockType->buildView($blockView, $block, $options);
        $this->assertEquals($expectedVars, $blockView->vars);
    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        return [
            'empty_options' => [
                [
                    'params' => [
                        'frontendOptions' => ['options' => []],
                        'buttonOptions' => [],
                        'actionUrl' => '',
                        'path' => '',
                        'label' => ''
                    ]
                    ,
                    'fromUrl' => '',
                ],
                [
                    'attr' => [
                        'href' => 'javascript:void(0);',
                        'class' => 'back icons-holder-text action-button',
                        'title' => '',
                        'data-from-url' => '',
                        'data-dialog-url' => '',
                        'data-dialog-options' => '{"title":"","dialogOptions":[]}',
                        'data-confirmation' => ''
                    ],
                    'linkLabel' => '',
                    'buttonOptions' => []
                ]
            ],
            'max_options' => [
                [
                    'params' => [
                        'id' => 'some_id',
                        'frontendOptions' => ['options' => ['some' => 'option'], 'show_dialog' => true],
                        'buttonOptions' => [
                            'page_component_module' => 'some_page_component_module',
                            'page_component_options' => 'some_page_component_options',
                            'data' => ['some' => 'data', 'one' => 'two']
                        ],
                        'actionUrl' => 'someActionUrl',
                        'path' => 'somePath',
                        'label' => 'someLabel'
                    ]
                    ,
                    'fromUrl' => 'someFromUrl',
                ],
                [
                    'attr' => [
                        'href' => 'somePath',
                        'class' => 'back icons-holder-text action-button',
                        'title' => 'someLabel',
                        'data-from-url' => 'someFromUrl',
                        'data-dialog-url' => 'someActionUrl',
                        'data-dialog-options' => '{"title":"someLabel","dialogOptions":{"some":"option"}}',
                        'data-confirmation' => '',
                        'id' => 'some_id',
                        'data-page-component-module' => 'some_page_component_module',
                        'data-page-component-options' => '"some_page_component_options"',
                        'data-some' => 'data',
                        'data-one' => 'two',
                    ],
                    'linkLabel' => 'someLabel',
                    'buttonOptions' => [
                        'page_component_module' => 'some_page_component_module',
                        'page_component_options' => 'some_page_component_options',
                        'data' => [
                            'some' => 'data',
                            'one' => 'two'
                        ]
                    ]
                ]
            ],
        ];
    }

    public function testSetDefaultOptions()
    {
        $resolver = new OptionsResolver();
        $this->blockType->setDefaultOptions($resolver);
        $this->assertEquals($resolver->getRequiredOptions(), ['params', 'fromUrl', 'actionData', 'context']);
    }
}
