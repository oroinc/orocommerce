<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CMSBundle\Entity\LoginPage;

class LoginPageProvider
{
    /**
     * @var array
     */
    protected $options = [];

    /**
     * @var ManagerRegistry
     */
    protected $managerRegistry;

    /**
     * @var string
     */
    protected $loginPageClass;

    /**
     * @param ManagerRegistry $managerRegistry
     */
    public function __construct(ManagerRegistry $managerRegistry)
    {
        $this->managerRegistry = $managerRegistry;
    }

    /**
     * @param string $loginPageClass
     */
    public function setLoginPageClass($loginPageClass)
    {
        $this->loginPageClass = $loginPageClass;
    }

    /**
     * @return LoginPage
     */
    public function getDefaultLoginPage()
    {
        if (!array_key_exists('default_login_page', $this->options)) {
            $repository = $this->managerRegistry->getRepository($this->loginPageClass);
            $loginPage = $repository->findOneBy([]);

            $this->options['default_login_page'] = !$loginPage ? new LoginPage() : $loginPage;
        }

        return $this->options['default_login_page'];
    }
}
