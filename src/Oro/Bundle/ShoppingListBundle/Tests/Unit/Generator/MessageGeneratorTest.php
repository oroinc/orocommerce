<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Generator;

use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\Translator;

class MessageGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|Translator */
    protected $translator;

    /** @var \PHPUnit\Framework\MockObject\MockObject|UrlGeneratorInterface */
    protected $router;

    /** @var MessageGenerator */
    protected $generator;

    protected function setUp()
    {
        $this->translator = $this->createMock('Symfony\Component\Translation\Translator');
        $this->router = $this->createMock('Symfony\Component\Routing\Generator\UrlGeneratorInterface');
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
            ->with('oro_shopping_list_frontend_view', ['id' => $shoppingListId])
            ->willReturn($url);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnMap(
                [
                    [
                        'oro.shoppinglist.actions.add_success_message',
                        ['%count%' => $entitiesCount],
                        null,
                        null,
                        $transChoice
                    ],
                    [
                        'oro.shoppinglist.actions.view',
                        [],
                        null,
                        null,
                        $withUrl ? $transMessage : null
                    ],
                ]
            );

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
