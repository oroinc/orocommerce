<?php

namespace Oro\Bundle\CMSBundle\Layout\DataProvider;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CMSBundle\Entity\LoginPage;

/**
 * Provides access to the default login page entity.
 *
 * Retrieves and caches the default login page from the database, allowing layout data providers
 * to access the configured login page for rendering in the storefront.
 */
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
