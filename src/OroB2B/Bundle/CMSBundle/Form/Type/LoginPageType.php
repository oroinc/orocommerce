<?php

namespace OroB2B\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginPageType extends AbstractType
{
    const NAME = 'orob2b_cms_login_page';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'topContent',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.entity.topContent.label',
                    'required' => false
                ]
            )
            ->add(
                'bottomContent',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.entity.bottomContent.label',
                    'required' => false
                ]
            )
            ->add(
                'css',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.entity.css.label',
                    'required' => false
                ]
            )
            ->add(
                'logoImage',
                'oro_image',
                [
                    'label'    => 'orob2b.cms.loginpage.entity.logoImage.label',
                    'required' => false
                ]
            )
            ->add(
                'backgroundImage',
                'oro_image',
                [
                    'label'    => 'orob2b.cms.loginpage.entity.backgroundImage.label',
                    'required' => false
                ]
            );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return self::NAME;
    }
}
