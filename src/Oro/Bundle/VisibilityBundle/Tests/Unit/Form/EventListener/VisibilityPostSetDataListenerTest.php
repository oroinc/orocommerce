<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Form\EventListener;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ScopeBundle\Tests\Unit\Stub\StubScope;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\AccountProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityFormFieldDataProvider;
use Oro\Bundle\VisibilityBundle\Form\EventListener\VisibilityPostSetDataListener;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\Test\FormInterface;

class VisibilityPostSetDataListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var VisibilityPostSetDataListener|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $listener;

    /**
     * @var VisibilityFormFieldDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $fieldDataProvider;

    public function setUp()
    {
        $this->fieldDataProvider = $this->getMockBuilder(VisibilityFormFieldDataProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new VisibilityPostSetDataListener(
            $this->fieldDataProvider
        );
    }

    public function testOnPostSetData()
    {
        $product = $this->getEntity(Product::class, ['id' => 1]);
        $form = $this->getMock(FormInterface::class);
        $formConfig = $this->getMock(FormConfigInterface::class);
        $formConfig->method('getOption')->with('allClass')->willReturn(ProductVisibility::class);
        $form->method('getData')->willReturn($product);
        $form->method('getConfig')->willReturn($formConfig);

        $account1 = $this->getEntity(Account::class, ['id' => 2]);
        $account2 = $this->getEntity(Account::class, ['id' => 4]);
        $accountGroup1 = $this->getEntity(AccountGroup::class, ['id' => 3]);
        $accountGroup2 = $this->getEntity(AccountGroup::class, ['id' => 5]);
        $this->fieldDataProvider->expects($this->exactly(3))
            ->method('findFormFieldData')
            ->willReturnMap(
                [
                    [$form, 'all', null],
                    [
                        $form,
                        'accountGroup',
                        [
                            3 => (new AccountGroupProductVisibility())->setVisibility('visible')
                                ->setScope(new StubScope(['accountGroup' => $accountGroup1, 'account' => null])),
                            5 => (new AccountGroupProductVisibility())->setVisibility('hidden')
                                ->setScope(new StubScope(['accountGroup' => $accountGroup2, 'account' => null])),
                        ],
                    ],
                    [
                        $form,
                        'account',
                        [
                            2 => (new AccountProductVisibility())->setVisibility('visible')
                                ->setScope(new StubScope(['accountGroup' => null, 'account' => $account1])),
                            4 => (new AccountGroupProductVisibility())->setVisibility('hidden')
                                ->setScope(new StubScope(['accountGroup' => null, 'account' => $account2])),
                        ],
                    ]
                ]
            );


        $allForm = $this->getMock(FormInterface::class);
        $accountForm = $this->getMock(FormInterface::class);
        $accountGroupForm = $this->getMock(FormInterface::class);

        $form->method('get')->willReturnMap(
            [
                ['all', $allForm],
                ['account', $accountForm],
                ['accountGroup', $accountGroupForm],
            ]
        );

        // assert data was set
        $allForm->expects($this->once())
            ->method('setData')
            ->with('category');

        $accountGroupForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    3 => ['entity' => $accountGroup1, 'data' => ['visibility' => 'visible']],
                    5 => ['entity' => $accountGroup2, 'data' => ['visibility' => 'hidden']],
                ]
            );
        $accountForm->expects($this->once())
            ->method('setData')
            ->with(
                [
                    2 => ['entity' => $account1, 'data' => ['visibility' => 'visible']],
                    4 => ['entity' => $account2, 'data' => ['visibility' => 'hidden']],
                ]
            );

        $event = new FormEvent($form, []);
        $this->listener->onPostSetData($event);
    }
}
