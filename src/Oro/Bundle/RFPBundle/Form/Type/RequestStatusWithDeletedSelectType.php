<?php

namespace Oro\Bundle\RFPBundle\Form\Type;

use Symfony\Component\OptionsResolver\OptionsResolverInterface;

use Oro\Bundle\RFPBundle\Entity\Repository\RequestStatusRepository;

class RequestStatusWithDeletedSelectType extends RequestStatusSelectType
{
    const NAME = 'oro_rfp_request_status_with_deleted_select';

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
