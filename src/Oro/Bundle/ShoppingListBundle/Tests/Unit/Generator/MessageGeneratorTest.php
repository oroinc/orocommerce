<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Generator;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use Oro\Bundle\ShoppingListBundle\Provider\ShoppingListUrlProvider;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Symfony\Contracts\Translation\TranslatorInterface;

class MessageGeneratorTest extends \PHPUnit\Framework\TestCase
{
    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var ShoppingListUrlProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $urlProvider;

    /** @var MessageGenerator */
    private $generator;

    protected function setUp(): void
    {
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->urlProvider = $this->createMock(ShoppingListUrlProvider::class);

        $doctrineHelper = $this->createMock(DoctrineHelper::class);
        $doctrineHelper->expects($this->any())
            ->method('getEntityReference')
            ->with(ShoppingList::class, 42)
            ->willReturn((new ShoppingListStub())->setId(42));

        $this->generator = new MessageGenerator($this->translator, $this->urlProvider, $doctrineHelper);
    }

    /**
     * @dataProvider getSuccessMessageDataProvider
     */
    public function testGetSuccessMessage(string $expectedMessage, int $entitiesCount, ?int $shoppingListId)
    {
        $withUrl = $entitiesCount && $shoppingListId;

        $url = '/test/url';
        $transChoice = 'transchoice';
        $transMessage = 'action';

        $this->urlProvider->expects($withUrl ? $this->once() : $this->never())
            ->method('getFrontendUrl')
            ->with((new ShoppingListStub())->setId(42))
            ->willReturn($url);

        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnMap([
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
            ]);

        $this->assertEquals($expectedMessage, $this->generator->getSuccessMessage($shoppingListId, $entitiesCount));
    }

    public function getSuccessMessageDataProvider(): array
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

        $this->urlProvider->expects($this->never())
            ->method($this->anything());

        $this->translator->expects($this->once())
            ->method('trans')
            ->willReturn($message);

        $this->assertEquals($message, $this->generator->getFailedMessage());
    }
}
