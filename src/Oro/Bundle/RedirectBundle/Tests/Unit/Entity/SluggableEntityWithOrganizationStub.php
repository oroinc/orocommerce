<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\OrganizationAwareInterface;
use Oro\Bundle\OrganizationBundle\Entity\Ownership\OrganizationAwareTrait;

class SluggableEntityWithOrganizationStub extends SluggableEntityStub implements OrganizationAwareInterface
{
    use OrganizationAwareTrait;
}
