<?php

namespace OroB2B\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginPageType extends AbstractType
{
    const NAME = 'orob2b_cms_login_page';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'topContent',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.top_content.label',
                    'required' => false
                ]
            )
            ->add(
                'bottomContent',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.bottom_content.label',
                    'required' => false
                ]
            )
            ->add(
                'css',
                'textarea',
                [
                    'label' => 'orob2b.cms.loginpage.css.label',
                    'required' => false
                ]
            )
            ->add(
                'logoImage',
                'oro_image',
                [
                    'label'    => 'orob2b.cms.loginpage.logo_image.label',
                    'required' => false
                ]
            )
            ->add(
                'backgroundImage',
                'oro_image',
                [
                    'label'    => 'orob2b.cms.loginpage.background_image.label',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
