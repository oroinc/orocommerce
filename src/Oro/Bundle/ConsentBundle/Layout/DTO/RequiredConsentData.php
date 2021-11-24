<?php

namespace Oro\Bundle\ConsentBundle\Layout\DTO;

use Oro\Bundle\ConsentBundle\Model\ConsentData;

/**
 * Holds information about accepted required consents and total required consents number.
 */
class RequiredConsentData
{
    private int $requiredConsentsNumber;

    /** @var ConsentData[] */
    private array $acceptedRequiredConsentData;

    public function __construct(array $acceptedRequiredConsentData = [], int $requiredConsentsNumber = 0)
    {
        $this->acceptedRequiredConsentData = $acceptedRequiredConsentData;
        $this->requiredConsentsNumber = $requiredConsentsNumber;
    }

    public function getAcceptedRequiredConsentData(): array
    {
        return $this->acceptedRequiredConsentData;
    }

    public function getRequiredConsentsNumber(): int
    {
        return $this->requiredConsentsNumber;
    }
}
