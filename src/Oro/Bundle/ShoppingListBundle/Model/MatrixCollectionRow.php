<?php

namespace Oro\Bundle\ShoppingListBundle\Model;

use Symfony\Component\Validator\Constraints as Assert;

/**
 * Matrix Row
 *
 */
class MatrixCollectionRow
{
    /**
     * @var string
     */
    public $label;

    /**
     * @var array|MatrixCollectionColumn[]
     */
    #[Assert\Valid]
    public $columns = [];
}
