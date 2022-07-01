<?php

namespace Oro\Bundle\ShoppingListBundle\Datagrid\GridView;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\DataGridBundle\Extension\GridViews\AbstractViewsList;
use Oro\Bundle\DataGridBundle\Extension\GridViews\View;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\DataGridBundle\Provider\State\ColumnsStateProvider;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\SecurityBundle\Acl\AccessLevel;
use Oro\Bundle\SecurityBundle\Acl\Domain\OneShotIsGrantedObserver;
use Oro\Bundle\SecurityBundle\Acl\Voter\AclVoterInterface;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides a list of grid views for frontend-customer-user-shopping-lists-grid.
 */
class FrontendShoppingListsViewsList extends AbstractViewsList
{
    /** @var AuthorizationCheckerInterface */
    private $authorizationChecker;

    /** @var AclVoterInterface */
    private $aclVoter;

    /** @var TokenAccessorInterface */
    private $tokenAccessor;

    public function __construct(
        TranslatorInterface $translator,
        AuthorizationCheckerInterface $authorizationChecker,
        AclVoterInterface $aclVoter,
        TokenAccessorInterface $tokenAccessor
    ) {
        parent::__construct($translator);

        $this->authorizationChecker = $authorizationChecker;
        $this->aclVoter = $aclVoter;
        $this->tokenAccessor = $tokenAccessor;
    }

    /**
     * {@inheritdoc}
     */
    protected function getViewsList(): array
    {
        $views = [];
        if (!$this->isViewAccessLevelBasic()) {
            $view = new View(
                'oro_shopping_list.my_shopping_lists',
                [
                    'owner' => [
                        'type' => (string)TextFilterType::TYPE_EQUAL,
                        'value' => $this->getFullName(),
                    ],
                ],
                [
                    'createdAt' => AbstractSorterExtension::DIRECTION_DESC,
                ],
                'system',
                [
                    'label' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 0,
                    ],
                    'subtotal' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 1,
                    ],
                    'lineItemsCount' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 2,
                    ],
                    'isDefault' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 3,
                    ],
                    'owner' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => false,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 4,
                    ],
                    'createdAt' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 5,
                    ],
                    'updatedAt' => [
                        ColumnsStateProvider::RENDER_FIELD_NAME => true,
                        ColumnsStateProvider::ORDER_FIELD_NAME => 6,
                    ],
                ]
            );

            $view
                ->setLabel($this->translator->trans('oro.frontend.shoppinglist.grid_view.my_shopping_lists'))
                ->setDefault(true);

            $views[] = $view;
        }

        return $views;
    }

    private function getFullName(): string
    {
        /** @var CustomerUser $customerUser */
        $customerUser = $this->tokenAccessor->getUser();
        if (!$customerUser instanceof CustomerUser) {
            throw new \LogicException('This grid view cannot work without customer user');
        }

        return $customerUser->getFullName();
    }

    private function isViewAccessLevelBasic(): int
    {
        $observer = new OneShotIsGrantedObserver();
        $this->aclVoter->addOneShotIsGrantedObserver($observer);
        $this->authorizationChecker->isGranted('oro_shopping_list_frontend_view');

        return $observer->getAccessLevel() === AccessLevel::BASIC_LEVEL;
    }
}
