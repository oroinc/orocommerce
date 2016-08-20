<?php

namespace Oro\Bundle\FrontendBundle\Form\Type\Configuration;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class WebType extends AbstractType
{
    const NAME = 'orob2b_frontend_install_configuration_web';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'oro_installer_web_backend_prefix',
            'text',
            [
                'label' => 'orob2b_frontend.form.install_configuration.web.backend_prefix',
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Regex(['pattern' => '~^/\w+$~', 'message' => 'orob2b_frontend.regex.backend_prefix'])
                ],
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
