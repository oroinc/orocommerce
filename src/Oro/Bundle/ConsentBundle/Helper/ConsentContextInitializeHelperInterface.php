<?php

namespace Oro\Bundle\ConsentBundle\Helper;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

/**
 * Interface that generalizes logic that processes consent context initialization
 */
interface ConsentContextInitializeHelperInterface
{
    /**
     * @param CustomerUser|null $customerUser
     * @param bool $force
     *
     * @return bool
     */
    public function initialize(CustomerUser $customerUser = null, $force = false);
}
