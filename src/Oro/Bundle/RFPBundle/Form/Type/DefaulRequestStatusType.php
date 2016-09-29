<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

class DefaulRequestStatusType extends AbstractType
{
    const NAME = 'oro_rfp_default_request_status';

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
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return static::NAME;
    }
}
