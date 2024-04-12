<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

interface UpdateWorkflowItemInterface
{
    public function execute(object $entity, array $data): array;
}
