<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

class RequestStatusSelectType extends AbstractType
{
    const NAME = 'orob2b_rfp_request_status_select';

    /**
     * @var string
     */
    protected $entityClass;

    /**
     * @param string $entityClass
     */
    public function setEntityClass($entityClass)
    {
        $this->entityClass = $entityClass;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'class'   => $this->entityClass,
                'query_builder' => function (RequestStatusRepository $repository) {
                    return $repository->getNotDeletedRequestStatusesQueryBuilder();
                },
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
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }
}
