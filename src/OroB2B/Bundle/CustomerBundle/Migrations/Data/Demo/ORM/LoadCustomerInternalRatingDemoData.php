<?php

namespace OroB2B\Bundle\CustomerBundle\Migrations\Data\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

class LoadCustomerInternalRatingData extends AbstractEnumFixture
{
    /**
     * Returns an array of possible enum values, where array key is an id and array value is an English translation
     *
     * @return array
     */
    protected function getData()
    {
       return [
            '1 of 5' => '1 of 5',
            '2 of 5' => '2 of 5',
            '3 of 5' => '3 of 5',
            '4 of 5' => '4 of 5',
            '5 of 5' => '5 of 5',
       ];
    }

    /**
     * Returns an enum code of an extend entity
     *
     * @return string
     */
    protected function getEnumCode()
    {
        return 'cust_internal_rating';
    }


}