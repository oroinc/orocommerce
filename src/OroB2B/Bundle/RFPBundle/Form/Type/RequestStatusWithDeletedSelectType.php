<?php

namespace OroB2B\Bundle\RFPBundle\Form\Type;

class RequestStatusWithDeletedSelectType extends RequestStatusSelectType
{
    const NAME = 'orob2b_rfp_request_status_with_deleted_select';

    /**
     * {@inheritdoc}
     */
    protected function getChoices()
    {
        return $this->registry->getRepository('OroB2BRFPBundle:RequestStatus')
            ->getNotDeletedAndDeletedWithRequestsStatuses();
    }
}
