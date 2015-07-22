<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

class DefaulRequestStatusType extends AbstractType
{
    const NAME = 'orob2b_rfp_default_request_status';

    /**
     * @var ManagerRegistry
     */
    protected $registry;

    /**
     * @var string
     */
    protected $requestStatusClass;

    /**
     * @param ManagerRegistry $registry
     */
    public function __construct(ManagerRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @param string $requestStatusClass
     */
    public function setRequestStatusClass($requestStatusClass)
    {
        $this->requestStatusClass = $requestStatusClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        /** @var RequestStatusRepository $repository */
        $repository = $this->registry->getRepository($this->requestStatusClass);
        $choicesRaw = $repository->getNotDeletedStatuses();

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
