<?php

namespace OroB2B\Bundle\RFPAdminBundle\Form\Type;

use OroB2B\Bundle\RFPAdminBundle\Entity\Repository\RequestStatusRepository;

class RequestStatusWithDeletedSelectType extends RequestStatusSelectType
{
    const NAME = 'orob2b_rfp_admin_request_status_with_deleted_select';

    /**
     * {@inheritdoc}
     */
    protected function getChoices()
    {
        /** @var RequestStatusRepository $repository */
        $repository = $this->registry->getRepository($this->entityClass);

        return $repository->getNotDeletedAndDeletedWithRequestsStatuses();
    }
}
