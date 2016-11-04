<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CustomerBundle\Helper\CustomerUserHelper;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

class FrontendAccountUserSelectType extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_select';

    /**
     * @var CustomerUserHelper
     */
    protected $customerUserHelper;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var string
     */
    protected $ownerFieldName;

    /**
     * @param CustomerUserHelper $customerUserHelper
     * @param AclHelper $aclHelper
     */
    public function __construct(CustomerUserHelper $customerUserHelper, AclHelper $aclHelper)
    {
        $this->customerUserHelper = $customerUserHelper;
        $this->aclHelper = $aclHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
        $builder->addEventListener(FormEvents::POST_SUBMIT, [$this, 'onPostSubmit']);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'class' => 'OroCustomerBundle:AccountUser',
                'required' => false,
                'choices' => $this->customerUserHelper->getAccountUsers($this->aclHelper),
                'mapped' => false,
                'configs' => [
                    'placeholder' => 'oro.customer.accountuser.form.choose',
                ],
            ]
        );
    }

    /**
     * @param FormEvent $event
     */
    public function onPreSetData(FormEvent $event)
    {
        $parent = $event->getForm()->getParent();
        $entity = $parent->getData();
        $ownerFieldName = $this->customerUserHelper->getOwnerFieldName($entity);
        $user = $this->customerUserHelper->getAccessorValue($entity, $ownerFieldName);
        $event->setData($user->getId());
    }

    /**
     * @param FormEvent $event
     */
    public function onPostSubmit(FormEvent $event)
    {
        $parent = $event->getForm()->getParent();
        $data = $event->getData();
        $entity = $parent->getData();
        $ownerFieldName = $this->customerUserHelper->getOwnerFieldName($entity);
        if ($data && $parent->has($ownerFieldName)) {
            /** @var AccountUser $user */
            $user = $this->customerUserHelper->getUserById($data);
            $this->customerUserHelper->setAccountUser($user, $entity);
        }
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
    public function getParent()
    {
        return 'genemu_jqueryselect2_choice';
    }
}
