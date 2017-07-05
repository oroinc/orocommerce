<?php

namespace Oro\Bundle\ProductBundle\Processor\Update;

use Oro\Bundle\ApiBundle\Request\JsonApi\JsonApiDocumentBuilder as JsonApi;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductUnitPrecisionRepository;
use Oro\Bundle\ProductBundle\Processor\Shared\ProcessUnitPrecisions;

class ProcessUnitPrecisionsUpdate extends ProcessUnitPrecisions
{
    /**
     * @param $unitPrecision
     * @param $pointer
     */
    protected function validateRequiredFields($unitPrecision, $pointer)
    {
        if (isset($unitPrecision[JsonApi::ID])) {
            $this->mandatoryFields = [];
        }
        parent::validateRequiredFields($unitPrecision, $pointer);
    }
}
