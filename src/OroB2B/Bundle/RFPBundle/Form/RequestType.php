<?php

namespace OroB2B\Bundle\RFPBundle\Form;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\ApplicationBundle\Config\ConfigManager;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_type';

    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ConfigManager $configManager
     * @param ManagerRegistry $registry
     */
    public function __construct(ConfigManager $configManager, ManagerRegistry $registry)
    {
        $this->configManager = $configManager;
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('firstName', 'text', [
                'label' => 'orob2b.rfp.request.first_name.label'
            ])
            ->add('lastName', 'text', [
                'label' => 'orob2b.rfp.request.last_name.label'
            ])
            ->add('email', 'text', [
                'label' => 'orob2b.rfp.request.email.label'
            ])
            ->add('phone', 'text', [
                'label' => 'orob2b.rfp.request.phone.label'
            ])
            ->add('company', 'text', [
                'label' => 'orob2b.rfp.request.company.label'
            ])
            ->add('role', 'text', [
                'label' => 'orob2b.rfp.request.role.label'
            ])
            ->add('body', 'textarea', [
                'label' => 'orob2b.rfp.request.body.label'
            ])
        ;

        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $form = $event->getForm();

        $form->add('status', 'entity', [
            'class' => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
            'data'  => $this->getDefaultRequestStatus()
        ]);
    }

    /**
     * @return RequestStatus
     */
    protected function getDefaultRequestStatus()
    {
        return $this->registry
            ->getManagerForClass('OroB2BRFPBundle:RequestStatus')
            ->getRepository('OroB2BRFPBundle:RequestStatus')
            ->findOneBy([
                'name' => $this->configManager->get('oro_b2b_rfp_admin.default_request_status')
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'OroB2B\Bundle\RFPBundle\Entity\Request'
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
