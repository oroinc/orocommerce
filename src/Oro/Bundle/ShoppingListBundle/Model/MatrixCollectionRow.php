<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

class MatrixCollectionRow
{
    /**
     * @var string
     */
    public $label;

    /**
     * @Assert\Valid
     * @var array|MatrixCollectionColumn[]
     */
    public $columns = [];
}
