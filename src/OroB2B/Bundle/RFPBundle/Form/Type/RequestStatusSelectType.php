<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;

class RequestStatusSelectType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_status_select';

    /**
     * {@inheritdoc}
     */
    protected function getChoices()
    {
        return $this->registry->getRepository('OroB2BRFPBundle:RequestStatus')->getNotDeletedStatuses();
    }

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
        $resolver->setDefaults(
            [
                'class'    => 'OroB2B\Bundle\RFPBundle\Entity\RequestStatus',
                'choices'  => $this->getChoices()
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'translatable_entity';
    }

    /**
     * @return RequestStatus[]
     */
    public function getName()
    {
        return static::NAME;
    }
}
