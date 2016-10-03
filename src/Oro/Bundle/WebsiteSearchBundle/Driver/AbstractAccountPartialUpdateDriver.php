<?php

namespace Oro\Bundle\WebsiteSearchBundle\Driver;

use Oro\Bundle\WebsiteSearchBundle\Placeholder\VisitorReplacePlaceholder;

abstract class AbstractAccountPartialUpdateDriver implements AccountPartialUpdateDriverInterface
{
    /**
     * @var VisitorReplacePlaceholder
     */
    private $visitorReplacePlaceholder;
}
