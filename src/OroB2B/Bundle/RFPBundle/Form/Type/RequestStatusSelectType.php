<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

class RequestStatusSelectType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_status_select';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $choices = $this->registry->getRepository('OroB2BRFPBundle:RequestStatus')->getNotDeletedStatuses();

        $resolver->setDefaults(
            [
                'class'   => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
                'choices' => $choices,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'entity';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
