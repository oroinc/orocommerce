<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\UnauthenticatedEnabledTestTrait;

/**
 * @group CommunityEdition
 */
class InventoryLevelForUnauthenticatedEnabledTest extends InventoryLevelForVisitorTest
{
    use UnauthenticatedEnabledTestTrait;
}
