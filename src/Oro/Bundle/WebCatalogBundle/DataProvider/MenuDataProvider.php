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
            self::IDENTIFIER => 'root/sales',
            self::LABEL => 'Sales',
            self::URL => '/sales',
            self::CHILDREN => [
                [
                    self::IDENTIFIER => 'root/sales/winter_sale',
                    self::LABEL => 'Winter Sale',
                    self::URL => '/sales/winter_sale',
                    self::CHILDREN => [
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_men',
                            self::LABEL => 'For Men',
                            self::URL => '/sales/winter_sale/for_men',
                            self::CHILDREN => []
                        ],
                        [
                            self::IDENTIFIER => 'root/sales/winter_sale/for_women',
                            self::LABEL => 'For Women',
                            self::URL => '/sales/winter_sale/for_women',
                            self::CHILDREN => []
                        ],
                    ]
                ]
            ]
        ],
        [
            self::IDENTIFIER => 'root/bestsellers',
            self::LABEL => 'Bestsellers',
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
        ]
    ];

    /**
     * @return array
     */
    public function getItems()
    {
        return $this->itemsData;
    }
}
