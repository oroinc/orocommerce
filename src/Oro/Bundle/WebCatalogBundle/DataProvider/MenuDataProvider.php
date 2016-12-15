<?php

namespace Oro\Bundle\WebCatalogBundle\DataProvider;

class MenuDataProvider
{
    const IDENTIFIER = 'identifier';
    const LABEL = 'label';
    const URL = 'url';
    const CHILDREN = 'children';

    /**
     * @var array
     */
    protected $itemsData = [
        [
            self::IDENTIFIER => 'with_head',
            self::LABEL => 'With Head',
            self::URL => '/sales',
            self::CHILDREN => [
                [
                    self::IDENTIFIER => 'root/sales/winter_sale',
                    self::LABEL => 'Winter Sale',
                    self::URL => '/sales/winter_sale',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/sales/summer_sale',
                    self::LABEL => 'Summer Sale',
                    self::URL => '/sales/summer_sale',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/sales/ny_sale',
                    self::LABEL => 'New Year Sale',
                    self::URL => '/sales/ny_sale',
                    self::CHILDREN => []
                ],
            ]
        ],
        [
            self::IDENTIFIER => 'simple',
            self::LABEL => 'Simple',
            self::URL => '/bestsellers',
            self::CHILDREN => [
                [
                    self::IDENTIFIER => 'root/bestsellers/for_men',
                    self::LABEL => 'For Men',
                    self::URL => '/bestsellers/for_men',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/bestsellers/for_women',
                    self::LABEL => 'For Women',
                    self::URL => '/bestsellers/for_women',
                    self::CHILDREN => []
                ],
            ]
        ],
        [
            self::IDENTIFIER => 'simple2',
            self::LABEL => 'Another Simple',
            self::URL => '/bestsellers2',
            self::CHILDREN => [
                [
                    self::IDENTIFIER => 'root/bestsellers/for_men2',
                    self::LABEL => 'For Men2',
                    self::URL => '/bestsellers/for_men',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/bestsellers/for_women2',
                    self::LABEL => 'For Women2',
                    self::URL => '/bestsellers/for_women',
                    self::CHILDREN => []
                ],
            ]
        ],
        [
            self::IDENTIFIER => 'link',
            self::LABEL => 'Simple Link',
            self::URL => '/extra',
            self::CHILDREN => []
        ],
        [
            self::IDENTIFIER => 'mega',
            self::LABEL => 'Mega',
            self::URL => '/sales',
            self::CHILDREN => [
                [
                    self::IDENTIFIER => 'root/sales/winter_sale1',
                    self::LABEL => 'Winter Sale',
                    self::URL => '/sales/winter_sale',
                    self::CHILDREN => [
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_men_2',
                            self::LABEL => 'For Men',
                            self::URL => '/sales/winter_sale/for_men',
                            self::CHILDREN => []
                        ],
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_women_2',
                            self::LABEL => 'For Women',
                            self::URL => '/sales/winter_sale/for_women',
                            self::CHILDREN => []
                        ],
                    ]
                ],
                [
                    self::IDENTIFIER => 'root/sales/winter_sale2',
                    self::LABEL => 'Summer Sale',
                    self::URL => '/sales/winter_sale',
                    self::CHILDREN => [
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_men_3',
                            self::LABEL => 'For Men',
                            self::URL => '/sales/winter_sale/for_men',
                            self::CHILDREN => []
                        ],
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_women_3',
                            self::LABEL => 'For Women',
                            self::URL => '/sales/winter_sale/for_women',
                            self::CHILDREN => []
                        ],
                    ]
                ],
                [
                    self::IDENTIFIER => 'root/sales/apple',
                    self::LABEL => 'Apple',
                    self::URL => '/sales/apple',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/sales/google',
                    self::LABEL => 'Google',
                    self::URL => '/sales/google',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/sales/shirts',
                    self::LABEL => 'Shirts',
                    self::URL => '/sales/shirts',
                    self::CHILDREN => []
                ],
                [
                    self::IDENTIFIER => 'root/sales/jackets',
                    self::LABEL => 'Jackets',
                    self::URL => '/sales/jackets',
                    self::CHILDREN => []
                ],
            ]
        ],
    ];

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->itemsData;
    }
}
