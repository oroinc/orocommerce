<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PayPalPasswordType extends PasswordType
{
    const NAME = 'oro_paypal_paypal_password_type';

    const PASSWORD_PLACEHOLDER = '*';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SUBMIT,
            function (FormEvent $event) {
                $data = $event->getData();
                if ($data === $this->getPlaceholder($data)) {
                    $event->setData($event->getForm()->getData());
                }
            }
        );
    }

    /**
     * @param string $data
     * @return string
     */
    protected function getPlaceholder($data)
    {
        return str_repeat(self::PASSWORD_PLACEHOLDER, strlen((string)$data));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['value'] = $this->getPlaceholder($form->getData());
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);
        $resolver->remove('always_empty');
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return self::NAME;
    }
}
