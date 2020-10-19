<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\GridView;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Datagrid\GridView\FrontendShoppingListsViewsList;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FrontendShoppingListsViewsListTest extends \PHPUnit\Framework\TestCase
{
    /** @var AuthorizationCheckerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $authorizationChecker;

    /** @var AclVoterInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $aclVoter;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var FrontendShoppingListsViewsList */
    private $provider;

    public function setUp(): void
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->aclVoter = $this->createMock(AclVoterInterface::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator
            ->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function ($id) {
                    return 'trans_' . $id;
                }
            );

        $this->provider = new FrontendShoppingListsViewsList(
            $translator,
            $this->authorizationChecker,
            $this->aclVoter,
            $this->tokenAccessor
        );
    }

    public function testGetSystemViewsList(): void
    {
        $this->assertEmpty($this->provider->getSystemViewsList());
    }

    public function testGetListWhenBasicAccessLevel(): void
    {
        $this->aclVoter
            ->expects($this->once())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(
                static function (OneShotIsGrantedObserver $observer) {
                    $observer->setAccessLevel(AccessLevel::BASIC_LEVEL);
                }
            );

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_view');

        $this->assertEmpty($this->provider->getList());
    }

    public function testGetListWhenNotCustomerUser(): void
    {
        $this->aclVoter
            ->expects($this->once())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(
                static function (OneShotIsGrantedObserver $observer) {
                    $observer->setAccessLevel(AccessLevel::DEEP_LEVEL);
                }
            );

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_view');

        $customerUser = $this->createMock(\stdClass::class);
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('This grid view cannot work without customer user');

        $this->provider->getList();
    }

    public function testGetListWhenNotBasicAccessLevel(): void
    {
        $this->aclVoter
            ->expects($this->once())
            ->method('addOneShotIsGrantedObserver')
            ->willReturnCallback(
                static function (OneShotIsGrantedObserver $observer) {
                    $observer->setAccessLevel(AccessLevel::DEEP_LEVEL);
                }
            );

        $this->authorizationChecker
            ->expects($this->once())
            ->method('isGranted')
            ->with('oro_shopping_list_frontend_view');

        $fullName = 'Sample Fullname';
        $customerUser = $this->createMock(CustomerUser::class);
        $customerUser
            ->expects($this->once())
            ->method('getFullName')
            ->willReturn($fullName);

        $this->tokenAccessor
            ->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        /** @var ArrayCollection|View[] $list */
        $list = $this->provider->getList();
        $this->assertCount(1, $list);
        $this->assertEquals('oro_shopping_list.my_shopping_lists', $list[0]->getName());
        $this->assertEquals(
            [
                'owner' => [
                    'type' => TextFilterType::TYPE_EQUAL,
                    'value' => $fullName,
                ],
            ],
            $list[0]->getFiltersData()
        );
        $this->assertEquals(['createdAt' => AbstractSorterExtension::DIRECTION_DESC], $list[0]->getSortersData());
        $this->assertEquals('system', $list[0]->getType());
        $this->assertEquals(
            ['owner' => [ColumnsStateProvider::RENDER_FIELD_NAME => false]],
            $list[0]->getColumnsData()
        );
        $this->assertEquals('trans_oro.frontend.shoppinglist.grid_view.my_shopping_lists', $list[0]->getLabel());
        $this->assertTrue($list[0]->isDefault());

        // Checks that list is cached.
        $this->assertSame($list, $this->provider->getList());
    }
}
