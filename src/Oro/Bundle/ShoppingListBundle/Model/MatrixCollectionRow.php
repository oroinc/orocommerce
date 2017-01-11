<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

class MatrixCollectionRow
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var array|MatrixCollectionColumn[]
     */
    public $columns = [];
}
