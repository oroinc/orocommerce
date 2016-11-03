<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Component\Form\FormBuilderInterface;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FrontendAccountUserSelectType extends AbstractType
{
    const NAME = 'oro_account_frontend_account_user_select';

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @param AclHelper $aclHelper
     * @param Registry $registry
     */
    public function __construct(AclHelper $aclHelper, Registry $registry)
    {
        $this->aclHelper = $aclHelper;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'onPreSubmit']);
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
                'choices' => $this->getAccountUsers(),
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
    public function onPreSubmit(FormEvent $event)
    {
        $form = $event->getForm();
        $data = $event->getData();
        if (!$data) {
            $data = $form->getParent()->getData();
        }

        $parent = $form->getParent();
        if ($data && $parent->has('frontendOwner')) {
//            $user = $this->registry->getManagerForClass('OroCustomerBundle:AccountUser')
//                ->getRepository('OroCustomerBundle:AccountUser')->findOneBy(['id' => $data]);
            $parent->get('frontendOwner')->setData($data);
        }
    }

    /**
     * @return array
     */
    public function getAccountUsers()
    {
        return $this->registry
            ->getManagerForClass('OroCustomerBundle:AccountUser')
            ->getRepository('OroCustomerBundle:AccountUser')
            ->getAccountUsers($this->aclHelper);
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
