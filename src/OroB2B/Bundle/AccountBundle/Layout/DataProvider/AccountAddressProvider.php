<?php

namespace OroB2B\Bundle\AccountBundle\Layout\DataProvider;

use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class AccountAddressProvider
{
    /** @var UrlGeneratorInterface */
    protected $router;

    /** @var FragmentHandler */
    protected $fragmentHandler;

    /** @var string */
    protected $listRouteName = 'orob2b_api_account_frontend_get_account_addresses';

    /** @var string */
    protected $createRouteName = 'orob2b_account_frontend_account_address_create';

    /** @var string */
    protected $updateRouteName = 'orob2b_account_frontend_account_address_update';

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
     * @param string $listRouteName
     */
    public function setListRouteName($listRouteName)
    {
        $this->listRouteName = $listRouteName;
    }

    /**
     * @param string $createRouteName
     */
    public function setCreateRouteName($createRouteName)
    {
        $this->createRouteName = $createRouteName;
    }

    /**
     * @param string $updateRouteName
     */
    public function setUpdateRouteName($updateRouteName)
    {
        $this->updateRouteName = $updateRouteName;
    }

    /**
     * @param Account $entity
     * @return array
     */
    public function getComponentOptions(Account $entity)
    {
        $addressListUrl = $this->router->generate($this->listRouteName, ['entityId' => $entity->getId()]);
        $addressCreateUrl = $this->router->generate($this->createRouteName, ['entityId' => $entity->getId()]);

        return [
            'entityId' => $entity->getId(),
            'addressListUrl' => $addressListUrl,
            'addressCreateUrl' => $addressCreateUrl,
            'addressUpdateRouteName' => $this->updateRouteName,
            'currentAddresses' => $this->fragmentHandler->render($addressListUrl),
        ];
    }
}
