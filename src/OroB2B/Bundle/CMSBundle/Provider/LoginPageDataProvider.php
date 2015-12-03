<?php

namespace OroB2B\Bundle\CMSBundle\Provider;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\DataProviderInterface;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;

class LoginPageDataProvider implements DataProviderInterface
{
    /**
     * @var LoginPage
     */
    protected $data;

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
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return 'orob2b_cms_login_page';
    }

    /**
     * {@inheritdoc}
     */
    public function getData(ContextInterface $context)
    {
        if ($this->data === null) {
            $this->data = $this->getDefaultLoginPage();
        }
        return $this->data;
    }

    /**
     * @return ObjectRepository
     */
    protected function getLoginPageRepository()
    {
        return $this->managerRegistry->getRepository($this->loginPageClass);
    }

    /**
     * @return LoginPage
     */
    public function getDefaultLoginPage()
    {
        $loginPage = $this->getLoginPageRepository()->findOneBy([]);
        if (!$loginPage) {
            $loginPage = new LoginPage();
        }
        return $loginPage;
    }
}
