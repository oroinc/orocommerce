<?php

class BasicLegalTypesDataProvider implements LegalTypesDataProviderInterface
{
    public static $availableLegalTypes = [
        'ag' => 'AG',
        'eg' => 'eG',
        'ek' => 'EK',
        'ev' => 'e.V.',
        'freelancer' => 'Freelancer',
        'gbr' => 'GbR',
        'gmbh' => 'GmbH',
        'gmbh_ig' => 'GmbH iG',
        'gmbh_co_kg' => 'GmbH & Co. KG',
        'kg' => 'KG',
        'kgaa' => 'KgaA',
        'ltd' => 'Ltd',
        'ltd_co_kg' => 'Ltd co KG',
        'ohg' => 'OHG',
        'offtl_einrichtung' => 'öffl. Einrichtung',
        'sonst_pers_ges' => 'Sonst. KapitalGes',
        'stiftung' => 'Stiftung',
        'ug' => 'UG',
        'einzel' => 'Einzelunternehmen, Kleingewerbe, Handelsvetreter',
    ];

    /**
     * @inheritDoc
     */
    public function getLegalTypes()
    {
        return static::$availableLegalTypes;
    }
}