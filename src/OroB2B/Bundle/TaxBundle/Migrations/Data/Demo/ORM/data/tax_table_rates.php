<?php

// The categories of organizations and/or items below that are excluded from sales tax collection in California
// are selected for demo and testing purposes only. More examples can be found in
// Sales and Use Tax: Exemptions and Exclusions (Publication 61) at http://www.boe.ca.gov/pdf/pub61.pdf

$accountTaxCodes = [
    'NON_EXEMPT' => [
        'description' => '',
        'account_groups' => ['All Customers', 'Wholesale Accounts', 'Partners'],
    ],
    'FOREIGN_GOVERNMENTS' => [
        'description' => <<<DESC
Foreign government entities are not "persons" for sales and use tax purposes. Sales by and purchases from such entities
are not subject to sales or use tax. In addition, the use of property by the entities is not subject to use tax.
However, sales to these entities in California are subject to sales tax except when a treaty requires an exemption.
See http://www.boe.ca.gov/lawguides/business/current/btlg/vol1/sutl/6005.html
DESC
        ,
        'accounts' => ['Account G'],
    ],
    'STATE_GOVERNMENTS' => [
        'description' => <<<DESC
State government entities, other than California state and local government entities, are not "persons" for sales
and use tax purposes. Sales by and purchases from such governmental entities are not subject to tax. The use
of property in California by other states is not taxable. However, sales in California to other states are subject
to sales tax. See http://www.boe.ca.gov/lawguides/business/current/btlg/vol1/sutl/6005.html
DESC
    ],
];

$productTaxCodes = [
    'TAXABLE_ITEMS' => [
        'description' => '',
        'products' => [
            '6BC45', '7BS72', '2JD90', '1GS46', '8TF72', '2TK59', '6UK81', '3UK92', '0RT28', '5GF68', '5TU10', '2JD29',
            '2CF67', '3TU20', '2JV62', '1GB82', '9OK21', '3YB32', '8BC37', '4HC51', '3LV37', '7NM98', '7YV41', '5GN30',
            '6UB33', '3RE23', '8NN54', '4QI22', '4KL66', '6LJ54', '8UF78', '5IF41', '3JK76', '5UB78', '6GH85', '6PM40',
            '3ET67', '3JK90', '7BS71', '5BM69', '8LG34', '8VS71', '1AB92', '5TJ23', '4PJ19', '2RW93', '8DO33', '5GE27',
            '1TB10', '2LM04', '7TY55', '5XY10', '9GQ28', '2WE71', '6VC22', 'XS56', '4HJ92', '9OL25', '7NQ22', '2EW02',
        ],
    ],
    'MEDICAL_IDENTIFICATION_TAGS' => [
        'description' => <<<DESC
Sales of medical identification tags are exempt from tax when furnished by a qualifying organization.
See http://www.boe.ca.gov/lawguides/business/current/btlg/vol1/sutl/6371.html
DESC
        ,
        'products' => ['TAG1', 'TAG2', 'TAG3'],
    ],
    'WHEELCHAIRS_CRUTCHES_CANES_WALKERS' => [
        'description' => <<<DESC
Sales to and purchases by persons of wheelchairs, crutches, canes, quad canes, white canes for the legally blind,
and walkers under the direction of a physician, are exempt from tax.
See http://www.boe.ca.gov/lawguides/business/current/btlg/vol1/sutl/6369-2.html
DESC
        ,
        'products' => ['WLCH1'],
    ],
];

$taxes = [
    'LOS_ANGELES_COUNTY_SALES_TAX' => ['rate' => 0.09, 'description' => 'Sales Tax'],
    'ORANGE_COUNTY_SALES_TAX' => ['rate' => 0.08, 'description' => 'Sales Tax'],
    'VENTURA_COUNTY_SALES_TAX' => ['rate' => 0.075, 'description' => 'Sales Tax'],
    'CULVER_CITY_SALES_TAX' => ['rate' => 0.095, 'description' => 'Sales Tax'],
    'SANTA_MONICA_SALES_TAX' => ['rate' => 0.095, 'description' => 'Sales Tax'],
];

$taxJurisdictions = [
    'LOS_ANGELES_COUNTY' => [
        'country' => 'US',
        'state' => 'CA',
        'zip_codes' => [
            ['start' => '90001', 'end' => '90039'],
            ['start' => '90041', 'end' => '90224'],
            ['start' => '90239', 'end' => '90278'],
            ['start' => '90290', 'end' => '90296'],
            '90304',
            ['start' => '90501', 'end' => '90609'],
            ['start' => '90640', 'end' => '90652'],
            ['start' => '90670', 'end' => '90671'],
            ['start' => '90701', 'end' => '90703'],
            ['start' => '90706', 'end' => '90717'],
            ['start' => '90723', 'end' => '90734'],
            ['start' => '90744', 'end' => '91316'],
            ['start' => '91321', 'end' => '91337'],
            ['start' => '91342', 'end' => '91357'],
            ['start' => '91364', 'end' => '91376'],
            ['start' => '91380', 'end' => '91618'],
            ['start' => '91702', 'end' => '91706'],
            ['start' => '91711', 'end' => '91724'],
            ['start' => '91740', 'end' => '91741'],
            ['start' => '91744', 'end' => '91750'],
            ['start' => '91754', 'end' => '91756'],
            ['start' => '91765', 'end' => '91780'],
            ['start' => '91788', 'end' => '91899'],
            '93510',
            ['start' => '93532', 'end' => '93539'],
            ['start' => '93543', 'end' => '93544'],
            ['start' => '93550', 'end' => '93553'],
        ],
        'description' => 'Los Angeles County',
    ],
    'ORANGE_COUNTY' => [
        'country' => 'US',
        'state' => 'CA',
        'zip_codes' => [
            ['start' => '90620', 'end' => '90630'],
            ['start' => '90720', 'end' => '90721'],
            ['start' => '90740', 'end' => '90743'],
            ['start' => '92602', 'end' => '92859'],
            ['start' => '92861', 'end' => '92871'],
            ['start' => '92885', 'end' => '92899'],
        ],
        'description' => 'Orange County',
    ],
    'VENTURA_COUNTY' => [
        'country' => 'US',
        'state' => 'CA',
        'zip_codes' => [
            ['start' => '91319', 'end' => '91320'],
            ['start' => '91358', 'end' => '91362'],
            '91377',
            ['start' => '93001', 'end' => '93012'],
            ['start' => '93015', 'end' => '93024'],
            '93040',
            '93042',
            ['start' => '93060', 'end' => '93066'],
            ['start' => '93094', 'end' => '93099'],
        ],
        'description' => 'Ventura County',
    ],
    'CULVER_CITY' => [
        'country' => 'US',
        'state' => 'CA',
        'zip_codes' => [
            ['start' => '90232', 'end' => '90233'],
        ],
        'description' => 'Culver City',
    ],
    'SANTA_MONICA' => [
        'country' => 'US',
        'state' => 'CA',
        'zip_codes' => [
            ['start' => '90401', 'end' => '90409'],
        ],
        'description' => 'Santa Monica',
    ],

];

$taxRules = [
    [
        'account_tax_code' => 'NON_EXEMPT',
        'product_tax_code' => 'TAXABLE_ITEMS',
        'tax_jurisdiction' => 'LOS_ANGELES_COUNTY',
        'tax' => 'LOS_ANGELES_COUNTY_SALES_TAX'
    ],
    [
        'account_tax_code' => 'NON_EXEMPT',
        'product_tax_code' => 'TAXABLE_ITEMS',
        'tax_jurisdiction' => 'ORANGE_COUNTY',
        'tax' => 'ORANGE_COUNTY_SALES_TAX'
    ],
    [
        'account_tax_code' => 'NON_EXEMPT',
        'product_tax_code' => 'TAXABLE_ITEMS',
        'tax_jurisdiction' => 'VENTURA_COUNTY',
        'tax' => 'VENTURA_COUNTY_SALES_TAX'
    ],
    [
        'account_tax_code' => 'NON_EXEMPT',
        'product_tax_code' => 'TAXABLE_ITEMS',
        'tax_jurisdiction' => 'CULVER_CITY',
        'tax' => 'CULVER_CITY_SALES_TAX'
    ],
    [
        'account_tax_code' => 'NON_EXEMPT',
        'product_tax_code' => 'TAXABLE_ITEMS',
        'tax_jurisdiction' => 'SANTA_MONICA',
        'tax' => 'SANTA_MONICA_SALES_TAX'
    ],
];

return [
    'account_tax_codes' => $accountTaxCodes,
    'product_tax_codes' => $productTaxCodes,
    'taxes' => $taxes,
    'tax_jurisdictions' => $taxJurisdictions,
    'tax_rules' => $taxRules,
];
