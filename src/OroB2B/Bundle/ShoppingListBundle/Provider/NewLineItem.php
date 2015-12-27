<?php

namespace OroB2B\Bundle\ShoppingListBundle\Provider;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Core\Security;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;

class NewLineItem implements DataProviderInterface
{
    /**
     * @var array
     */
    protected $data;

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(
        SecurityFacade $securityFacade
    ) {
        $this->securityFacade = $securityFacade;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_shopping_list_new_line_item';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if ($this->data !== null) {
            return $this->data;
        }

        $accountUser = $this->securityFacade->getLoggedUser();
        if ($accountUser) {
            return null;
        }

        $this->data = (new LineItem())
            //->setProduct($product)
            ->setAccountUser($accountUser)
            ->setOrganization($accountUser->getOrganization());

        return $this->data;
    }
}
