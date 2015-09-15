<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Generator;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

use OroB2B\Bundle\ShoppingListBundle\Generator\MessageGenerator;

class MessageGeneratorTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface */
    protected $translator;

    /** @var \PHPUnit_Framework_MockObject_MockObject|UrlGeneratorInterface */
    protected $router;

    /** @var MessageGenerator */
    protected $generator;

    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->router = $this->getMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
        $this->generator = new MessageGenerator($this->translator, $this->router);
    }

    protected function tearDown()
    {
        unset($this->translator, $this->router, $this->generator);
    }

    /**
     * @dataProvider getSuccessMessageDataProvider
     *
     * @param string $expectedMessage
     * @param int $entitiesCount
     * @param null $shoppingListId
     */
    public function testGetSuccessMessage($expectedMessage, $entitiesCount = 0, $shoppingListId = null)
    {
        $withUrl = $entitiesCount && $shoppingListId;

        $url = '/test/url';
        $transChoice = 'transchoice';
        $transMessage = 'action';

        $this->router->expects($withUrl ? $this->once() : $this->never())
            ->method('generate')
            ->with('orob2b_shopping_list_frontend_view', ['id' => $shoppingListId])
            ->willReturn($url);

        $this->translator->expects($this->once())
            ->method('transChoice')
            ->with('orob2b.shoppinglist.actions.add_success_message', $entitiesCount, ['%count%' => $entitiesCount])
            ->willReturn($transChoice);

        $this->translator->expects($withUrl ? $this->once() : $this->never())
            ->method('trans')
            ->with('orob2b.shoppinglist.actions.view')
            ->willReturn($transMessage);

        $this->assertEquals($expectedMessage, $this->generator->getSuccessMessage($shoppingListId, $entitiesCount));
    }

    /**
     * @return array
     */
    public function getSuccessMessageDataProvider()
    {
        return [
            [
                'expectedMessage' => 'transchoice (<a href="/test/url">action</a>).',
                'entitiesCount' => 10,
                'shoppingListId' => 42
            ],
            [
                'expectedMessage' => 'transchoice',
                'entitiesCount' => 0,
                'shoppingListId' => 42
            ],
            [
                'expectedMessage' => 'transchoice',
                'entitiesCount' => 10,
                'shoppingListId' => null
            ]
        ];
    }

    public function testGetFailedMessage()
    {
        $message = 'test message';

        $this->router->expects($this->never())
            ->method($this->anything());

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($message);

        $this->assertEquals($message, $this->generator->getFailedMessage());
    }
}
