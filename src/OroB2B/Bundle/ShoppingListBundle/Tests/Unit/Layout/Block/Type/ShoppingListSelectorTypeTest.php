<?php

namespace Oro\Bundle\EntityConfigBundle\Tests\Unit\Form\Type;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Component\Layout\BlockInterface;
use Oro\Component\Layout\ContextDataCollection;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\BlockView;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ShoppingListBundle\Layout\Block\Type\ShoppingListSelectorType;

class ShoppingListSelectorTypeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ShoppingListSelectorType */
    protected $blockType;

    protected function setUp()
    {
        $this->blockType = new ShoppingListSelectorType();
    }

    public function testSetDefaultOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects($this->once())
            ->method('setRequired')
            ->with(['shoppingLists']);

        $this->blockType->setDefaultOptions($resolver);
    }

    /**
     * @dataProvider buildViewDataProvider
     * @param array $shoppingLists
     * @param ShoppingList|null $selectedShoppingList
     */
    public function testBuildView(array $shoppingLists, $selectedShoppingList)
    {
        $view = new BlockView();

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextInterface $block */
        $context = $this->getMock('Oro\Component\Layout\ContextInterface');

        /** @var \PHPUnit_Framework_MockObject_MockObject|ContextDataCollection $block */
        $contextDataCollection = $this->getMockBuilder('Oro\Component\Layout\ContextDataCollection')
            ->disableOriginalConstructor()
            ->getMock();

        $contextDataCollection->expects($this->once())
            ->method('has')
            ->with('shoppingList')
            ->willReturn($selectedShoppingList ? true : false);

        /** @var \PHPUnit_Framework_MockObject_MockObject|BlockInterface $block */
        $block = $this->getMock('Oro\Component\Layout\BlockInterface');

        $block->expects($this->any())
            ->method('getContext')
            ->willReturn($context);

        $context->expects($this->any())
            ->method('data')
            ->willReturn($contextDataCollection);

        if ($selectedShoppingList) {
            $contextDataCollection->expects($this->once())
                ->method('get')
                ->with('shoppingList')
                ->willReturn($selectedShoppingList);
        }

        $this->blockType->buildView($view, $block, ['shoppingLists' => $shoppingLists]);

        $this->assertArrayHasKey('shoppingLists', $view->vars);
        $this->assertEquals($shoppingLists, $view->vars['shoppingLists']);

        $this->assertArrayHasKey('selectedShoppingList', $view->vars);
        if ($selectedShoppingList) {
            $this->assertEquals($selectedShoppingList->getId(), $view->vars['selectedShoppingList']);
        } else {
            $this->assertNull($view->vars['selectedShoppingList']);
        }

    }

    /**
     * @return array
     */
    public function buildViewDataProvider()
    {
        $shoppingList1 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 1]);
        $shoppingList2 = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        $shoppingLists = [$shoppingList1, $shoppingList2];
        $selectedShoppingList = $shoppingList1;

        return [
            'with selected shopping list' => [
                'shoppingLists' => $shoppingLists,
                'selectedShoppingList' => $selectedShoppingList
            ],
            'without selected shopping list' => [
                'shoppingLists' => $shoppingLists,
                'selectedShoppingList' => null
            ],
        ];
    }

    public function testGetName()
    {
        $this->assertEquals($this->blockType->getName(), ShoppingListSelectorType::NAME);
    }
}
