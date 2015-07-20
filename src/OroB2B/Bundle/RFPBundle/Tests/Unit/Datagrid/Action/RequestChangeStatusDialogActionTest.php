<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\Unit\Datagrid\Action;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration;

use OroB2B\Bundle\RFPBundle\Datagrid\Action\RequestChangeStatusDialogAction;

class RequestChangeStatusDialogActionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider actionOptions
     *
     * @param array $actionOptions
     * @param string $link
     * @param string $title
     * @param string $translatedTitle
     */
    public function testGetOptions(array $actionOptions, $link, $title, $translatedTitle)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface $translator */
        $translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        if (count($actionOptions) > 1) {
            $translator->expects($this->once())
                ->method('trans')
                ->with($title)
                ->will($this->returnValue($translatedTitle));
        }

        $actionConfiguration = ActionConfiguration::create($actionOptions);

        $action = new RequestChangeStatusDialogAction($translator);
        $action->setOptions($actionConfiguration);

        $options = $action->getOptions();

        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction', $action);
        $this->assertInstanceOf('Oro\Bundle\DataGridBundle\Extension\Action\ActionConfiguration', $options);

        $this->assertCount(count($actionOptions) + 1, $options);
        $this->assertArrayHasKey('launcherOptions', $options);

        $this->assertArrayHasKey('link', $options);
        $this->assertEquals($link, $options['link']);

        $this->assertArrayHasKey('onClickReturnValue', $options['launcherOptions']);
        $this->assertTrue($options['launcherOptions']['onClickReturnValue']);

        $this->assertArrayHasKey('runAction', $options['launcherOptions']);
        $this->assertTrue($options['launcherOptions']['runAction']);

        $this->assertArrayHasKey('className', $options['launcherOptions']);
        $this->assertEquals('no-hash', $options['launcherOptions']['className']);

        $this->assertArrayHasKey('widget', $options['launcherOptions']);
        $this->assertEquals([], $options['launcherOptions']['widget']);

        $this->assertArrayHasKey('messages', $options['launcherOptions']);
        $this->assertEquals([], $options['launcherOptions']['messages']);

        if ($translatedTitle) {
            $this->assertArrayHasKey('widgetOptions', $options);
            $this->assertArrayHasKey('options', $options['widgetOptions']);
            $this->assertArrayHasKey('dialogOptions', $options['widgetOptions']['options']);
            $this->assertArrayHasKey('title', $options['widgetOptions']['options']['dialogOptions']);
            $this->assertEquals($translatedTitle, $options['widgetOptions']['options']['dialogOptions']['title']);
        }
    }

    /**
     * @return array
     */
    public function actionOptions()
    {
        $link = 'http://localhost';
        $title = 'Test Title';

        return [
            'with title' => [
                'actionOptions' => [
                    'link' => $link,
                    'widgetOptions' => [
                        'options' => [
                            'dialogOptions' => [
                                'title' => $title
                            ]
                        ]
                    ]
                ],
                'link' => 'http://localhost',
                'title' => $title,
                'translatedTitle' => 'Translated Title',
            ],
            'without title' => [
                'actionOptions' => [
                    'link' => $link,
                ],
                'link' => $link,
                'title' => null,
                'translatedTitle' => null,
            ],
        ];
    }
}
