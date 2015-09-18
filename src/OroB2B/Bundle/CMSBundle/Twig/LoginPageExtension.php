<?php

namespace OroB2B\Bundle\CMSBundle\Twig;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\AttachmentBundle\Entity\File;
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
            new \Twig_SimpleFunction('orob2b_login_css', [$this, 'getCss']),
            new \Twig_SimpleFunction('orob2b_login_top_content', [$this, 'getTopContent']),
            new \Twig_SimpleFunction('orob2b_login_bottom_content', [$this, 'getBottomContent']),
            new \Twig_SimpleFunction('orob2b_login_logo_image', [$this, 'getLogoImage']),
            new \Twig_SimpleFunction('orob2b_login_background_image', [$this, 'getBackgroundImage']),
        ];
    }

    /**
     * @return string
     */
    public function getCss()
    {
        return $this->getDefaultLoginPage()->getCss();
    }

    /**
     * @return string
     */
    public function getTopContent()
    {
        return $this->getDefaultLoginPage()->getTopContent();
    }

    /**
     * @return string
     */
    public function getBottomContent()
    {
        return $this->getDefaultLoginPage()->getBottomContent();
    }

    /**
     * @return File
     */
    public function getLogoImage()
    {
        $image = $this->getDefaultLoginPage()->getLogoImage();
        if (!$image) {
            return;
        }

        return $image->getFilename();
    }

    /**
     * @return File
     */
    public function getBackgroundImage()
    {
        $image = $this->getDefaultLoginPage()->getBackgroundImage();
        if (!$image) {
            return;
        }

        return $image->getFilename();
    }

    /**
     * @return LoginPage
     */
    protected function getDefaultLoginPage()
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
