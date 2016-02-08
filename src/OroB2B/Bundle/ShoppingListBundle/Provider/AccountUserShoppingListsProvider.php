<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LayoutBundle\Layout\Form\FormAccessor;
use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;

class AccountUserShoppingListsProvider implements DataProviderInterface
{
    const DATA_FORMAT_VAR_NAME = 'shopping_lists_format';

    const DATA_FORMAT_SINGLE_COLLECTION = 'collection';
    const DATA_FORMAT_CURRENT_LIST_SEPARATED = 'current_list_separated';

    const DATA_SORT_BY_UPDATED = 'updated';

    /**
     * @var FormAccessor
     */
    protected $data;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var string
     */
    protected $shoppingListClass;

    /**
     * @param DoctrineHelper $doctrineHelper
     * @param SecurityFacade $securityFacade
     * @param RequestStack $requestStack
     */
    public function __construct(
        DoctrineHelper $doctrineHelper,
        SecurityFacade $securityFacade,
        RequestStack $requestStack
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->securityFacade = $securityFacade;
        $this->requestStack = $requestStack;
    }

    /**
     * @param string $shoppingListClass
     */
    public function setShoppingListClass($shoppingListClass)
    {
        $this->shoppingListClass = $shoppingListClass;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_shopping_list_account_user_shopping_lists';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        $format = self::DATA_FORMAT_CURRENT_LIST_SEPARATED;
        if ($context->has(self::DATA_FORMAT_VAR_NAME)) {
            $format = $context->get(self::DATA_FORMAT_VAR_NAME);
        }
        if (!$this->data) {
            $this->data = $this->getAccountUserShoppingLists($format);
        }

        return $this->data;
    }

    /**
     * @param string $format
     * @return array|null
     * @throws \InvalidArgumentException
     */
    protected function getAccountUserShoppingLists($format)
    {
        $accountUser = $this->securityFacade->getLoggedUser();
        if (!$accountUser) {
            return null;
        }

        /** @var ShoppingListRepository $shoppingListRepository */
        $shoppingListRepository = $this->doctrineHelper->getEntityRepositoryForClass($this->shoppingListClass);

        switch ($format) {
            case self::DATA_FORMAT_SINGLE_COLLECTION:
                $data = $shoppingListRepository->findByUser($accountUser, $this->getSortOrder());
                break;
            case self::DATA_FORMAT_CURRENT_LIST_SEPARATED:
                $data = [
                    'shoppingLists' => $shoppingListRepository->findAllExceptCurrentForAccountUser($accountUser),
                    'currentShoppingList' => $shoppingListRepository->findCurrentForAccountUser($accountUser),
                ];
                break;
            default:
                throw new \InvalidArgumentException('Unknown data format');
        }

        return $data;
    }

    /**
     * @return string
     */
    protected function getSortOrder()
    {
        $sortOrder = [];
        $sort = $this->requestStack->getCurrentRequest()->get('sort');
        switch ($sort) {
            case self::DATA_SORT_BY_UPDATED:
                $sortOrder['list.updatedAt'] = 'desc';
                break;
            default:
                $sortOrder['list.updatedAt'] = 'desc';
        }

        return $sortOrder;
    }
}
