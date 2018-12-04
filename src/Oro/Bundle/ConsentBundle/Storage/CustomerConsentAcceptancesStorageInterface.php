<?php

namespace Oro\Bundle\ConsentBundle\Storage;

use Oro\Bundle\ConsentBundle\Entity\ConsentAcceptance;

/**
 * Interface that generally describes logic to save and restore data about selected customer user consents.
 * It will be useful in case when business flow required accepted consents
 * earlier than customerUser will be created (f.e. checkout workflow)
 */
interface CustomerConsentAcceptancesStorageInterface
{
    /**
     * @param ConsentAcceptance[] $consentAcceptances
     */
    public function saveData(array $consentAcceptances);

    /**
     * @return ConsentAcceptance[]
     */
    public function getData();

    public function clearData();

    /**
     * @return bool
     */
    public function hasData();
}
