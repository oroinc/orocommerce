<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

class FrontendCustomerUserType extends AbstractType
{
    const NAME = 'oro_customer_frontend_customer_user';

    /**
     * @var SecurityFacade
     */
    protected $securityFacade;

    /**
     * @var string
     */
    protected $customerUserClass;

    /**
     * @param SecurityFacade $securityFacade
     */
    public function __construct(SecurityFacade $securityFacade)
    {
        $this->securityFacade = $securityFacade;
    }

    /**
     * @param string $class
     */
    public function setCustomerUserClass($class)
    {
        $this->customerUserClass = $class;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->remove('salesRepresentatives');
        $builder->add(
            'roles',
            FrontendCustomerUserRoleSelectType::NAME,
            [
                'label' => 'oro.customer.customeruser.roles.label'
            ]
        );
    }

    /**
     * @param FormEvent $event
     * @return bool
     */
    public function onPreSetData(FormEvent $event)
    {
        /** @var $user CustomerUser */
        $user = $this->securityFacade->getLoggedUser();
        if (!$user instanceof CustomerUser) {
            return;
        }
        /** @var CustomerUser $data */
        $data = $event->getData();

        $event->getForm()->add('customer', FrontendOwnerSelectType::NAME, [
            'label' => 'oro.customer.customer.entity_label',
            'targetObject' => $data
        ]);

        $data->setOrganization($user->getOrganization());
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return CustomerUserType::NAME;
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

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->customerUserClass,
        ]);
    }
}
