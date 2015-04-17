<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

class DefaulRequestStatusType extends AbstractType
{
    const NAME = 'orob2b_rfp_default_request_status';

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
        $choicesRaw = $this->registry->getRepository('OroB2BRFPAdminBundle:RequestStatus')->getNotDeletedStatuses();

        $choices = [];

        foreach ($choicesRaw as $choiceRaw) {
            $choices[$choiceRaw->getName()] = $choiceRaw->getLabel();
        }

        $resolver->setDefaults(
            [
                'choices'  => $choices
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
