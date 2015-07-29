<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use OroB2B\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

class RequestStatusWithDeletedSelectType extends RequestStatusSelectType
{
    const NAME = 'orob2b_rfp_request_status_with_deleted_select';

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'query_builder' => function (RequestStatusRepository $repository) {
                    return $repository->getNotDeletedAndDeletedWithRequestsStatusesQueryBuilder();
                },
            ]
        );
    }
}
