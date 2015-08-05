<?php

namespace OroB2B\Bundle\AccountBundle\Migrations\Data\Demo\ORM;

use Oro\Bundle\EntityExtendBundle\Migration\Fixture\AbstractEnumFixture;

use OroB2B\Bundle\AccountBundle\Entity\Account;

class LoadAccountInternalRatingDemoData extends AbstractEnumFixture
{
    /**
     * Returns an array of possible enum values, where array key is an id and array value is an English translation
     *
     * @return array
     */
    protected function getData()
    {
        return [
            '1_of_5' => '1 of 5',
            '2_of_5' => '2 of 5',
            '3_of_5' => '3 of 5',
            '4_of_5' => '4 of 5',
            '5_of_5' => '5 of 5',
        ];
    }

    /**
     * Returns an enum code of an extend entity
     *
     * @return string
     */
    protected function getEnumCode()
    {
        return Account::INTERNAL_RATING_CODE;
    }
}
