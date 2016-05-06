<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
     * @param true $isProduct
     * @param bool $isAccountUser
     */
    public function testGetData($isProduct, $isAccountUser)
    {
        $context = new LayoutContext();
        $product = null;
        if ($isProduct) {
            $product = new Product();
            $context->data()->set('product', null, $product);
        }
        $this->setUpAccount($isAccountUser);

        $actual = $this->dataProvider->getData($context);
        $form = $actual->getForm();

        $this->assertInstanceOf('\Oro\Bundle\LayoutBundle\Layout\Form\FormAccessorInterface', $actual);
        $this->assertSame($this->dataProvider->getForm($product), $form);
        $lineItem = $form->getData();
        $this->assertLineItem($isAccountUser, $product, $lineItem);
        $this->assertEquals(FrontendLineItemType::NAME, $actual->getForm()->getName());
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            ['isProduct' => true, 'isAccountUser' => true],
            ['isProduct' => true, 'isAccountUser' => false],
            ['isProduct' => false, 'isAccountUser' => true],
            ['isProduct' => false, 'isAccountUser' => false],
        ];
    }

    /**
     * @param bool $isAccountUser
     */
    protected function setUpAccount($isAccountUser)
    {
        $accountUser = null;
        if ($isAccountUser) {
            $accountUser = $this->getMock('OroB2B\Bundle\AccountBundle\Entity\AccountUser');
            $organization = $this->getMock('Oro\Bundle\OrganizationBundle\Entity\OrganizationInterface');
            $accountUser->expects($this->any())
                ->method('getOrganization')
                ->willReturn($organization);
        }
        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accountUser);
    }

    /**
     * @param bool $isAccountUser
     * @param Product|null $product
     * @param LineItem|null $lineItem
     */
    protected function assertLineItem($isAccountUser = false, Product $product = null, LineItem $lineItem = null)
    {
        if ($isAccountUser) {
            $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', $lineItem);
            $this->assertSame($product, $lineItem->getProduct());
            return;
        }
        $this->assertNull($lineItem);
    }
}
