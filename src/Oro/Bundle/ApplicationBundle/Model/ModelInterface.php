<?php

namespace Oro\Bundle\ApplicationBundle\Model;

/**
 * Model interface should be implemented by all model entities to provide
 * important functionality for entity-model interaction
 */
interface ModelInterface
{
    /**
     * Returns list of entities used inside model as a data storage -
     * these entities are used to save and delete model data
     *
     * @return array|object[]
     */
    public function getEntities();

    /**
     * Returns unique identifier of model type used to generate event names
     * and other model dependent variables
     *
     * @return string
     */
    public static function getModelName();
}
