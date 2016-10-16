<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Fixtures\Entity;

use Oro\Bundle\AccountBundle\Entity\AccountUser as ParentUser;

class User extends ParentUser
{
    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }
}
