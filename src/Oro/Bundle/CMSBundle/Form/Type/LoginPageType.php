<?php

namespace Oro\Bundle\CMSBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class LoginPageType extends AbstractType
{
    const NAME = 'oro_cms_login_page';

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
                    'label' => 'oro.cms.loginpage.top_content.label',
                    'required' => false
                ]
            )
            ->add(
                'bottomContent',
                'textarea',
                [
                    'label' => 'oro.cms.loginpage.bottom_content.label',
                    'required' => false
                ]
            )
            ->add(
                'css',
                'textarea',
                [
                    'label' => 'oro.cms.loginpage.css.label',
                    'required' => false
                ]
            )
            ->add(
                'logoImage',
                'oro_image',
                [
                    'label'    => 'oro.cms.loginpage.logo_image.label',
                    'required' => false
                ]
            )
            ->add(
                'backgroundImage',
                'oro_image',
                [
                    'label'    => 'oro.cms.loginpage.background_image.label',
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
