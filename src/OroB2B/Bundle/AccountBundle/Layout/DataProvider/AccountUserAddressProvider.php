<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;

class AccountUserAddressProvider
{
    const ACCOUNT_USER_ADDRESS_LIST_ROUTE_NAME = 'orob2b_api_account_frontend_get_accountuser_addresses';
    const ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_address_create';
    const ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME = 'orob2b_account_frontend_account_user_address_update';

    /**
     * @var UrlGeneratorInterface
     */
    protected $router;

    /**
     * @var FragmentHandler
     */
    protected $fragmentHandler;

    /**
     * @param UrlGeneratorInterface $router
     * @param FragmentHandler $fragmentHandler
     */
    public function __construct(UrlGeneratorInterface $router, FragmentHandler $fragmentHandler)
    {
        $this->router = $router;
        $this->fragmentHandler = $fragmentHandler;
    }

    /**
     * @param AccountUser $entity
     * @return array
     */
    public function getComponentOptions(AccountUser $entity)
    {
        $addressListUrl = $this->router->generate(
            self::ACCOUNT_USER_ADDRESS_LIST_ROUTE_NAME,
            ['entityId' => $entity->getId()]
        );
        $addressCreateUrl = $this->router->generate(
            self::ACCOUNT_USER_ADDRESS_CREATE_ROUTE_NAME,
            ['entityId' => $entity->getId()]
        );

        return [
            'entityId' => $entity->getId(),
            'addressListUrl' => $addressListUrl,
            'addressCreateUrl' => $addressCreateUrl,
            'addressUpdateRouteName' => self::ACCOUNT_USER_ADDRESS_UPDATE_ROUTE_NAME,
            'currentAddresses' => $this->fragmentHandler->render($addressListUrl),
        ];
    }
}
