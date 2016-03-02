<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendLineItemFormProvider;

class FrontendLineItemFormProviderTest extends WebTestCase
{
    /** @var FrontendLineItemFormProvider */
    protected $dataProvider;

    /** @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject */
    protected $securityFacade;

    protected function setUp()
    {
        $this->initClient();

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()->getMock();
        $this->dataProvider = new FrontendLineItemFormProvider(
            $this->getContainer()->get('form.factory'),
            $this->securityFacade
        );
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param Product|null $product
     * @param AccountUser|null $accountUser
     */
    public function testGetData(Product $product = null, AccountUser $accountUser = null)
    {
        $context = new LayoutContext();
        if ($product) {
            $context->data()->set('product', null, $product);
        }
        $this->setUpAccount($accountUser);

        $actual = $this->dataProvider->getData($context);
        $form = $actual->getForm();

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm($product), $form);
        $lineItem = $form->getData();
        $this->assertLineItem($accountUser, $product, $lineItem);
        $this->assertEquals(FrontendLineItemType::NAME, $actual->getForm()->getName());
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [
                'product' => new Product(),
                'account' => $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser'),
            ],
            [
                'product' => new Product(),
                'account' => null,
            ],
            [
                'product' => null,
                'account' => $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser'),
            ],
            [
                'product' => null,
                'account' => null,
            ],
        ];
    }

    /**
     * @param AccountUser|null $accountUser
     * @return AccountUser
     */
    protected function setUpAccount(AccountUser $accountUser = null)
    {
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);
    }

    /**
     * @param AccountUser|null $accountUser
     * @param Product|null $product
     * @param LineItem|null $lineItem
     */
    protected function assertLineItem(
        AccountUser $accountUser = null,
        Product $product = null,
        LineItem $lineItem = null
    ) {
        if ($accountUser) {
            $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', $lineItem);
            $this->assertSame($product, $lineItem->getProduct());
            return;
        }
        $this->assertNull($lineItem);
    }
}
