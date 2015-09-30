<?php

namespace OroB2B\Bundle\CMSBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;

use OroB2B\Bundle\CMSBundle\Entity\LoginPage;

class LoginPageExtension extends \Twig_Extension
{
    const NAME = 'orob2b_cms_login_page_extension';

    /** @var ManagerRegistry */
    protected $doctrine;

    /** @var ObjectRepository */
    protected $loginPageRepository;

    /**
     * @param ManagerRegistry $doctrine
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('orob2b_login_page', [$this, 'getDefaultLoginPage'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @return LoginPage
     */
    public function getDefaultLoginPage()
    {
        // Get one default login page
        return $this->getLoginPageRepository()->findOneBy([]);
    }

    /**
     * @return ObjectRepository
     */
    protected function getLoginPageRepository()
    {
        if (!$this->loginPageRepository) {
            $this->loginPageRepository = $this->doctrine->getManagerForClass('OroB2BCMSBundle:LoginPage')
                ->getRepository('OroB2BCMSBundle:LoginPage');
        }

        return $this->loginPageRepository;
    }
}
