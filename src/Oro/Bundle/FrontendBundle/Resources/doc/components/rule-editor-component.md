#Rule editor component
Rule editor is a component add-on that implements autocompletion function to any input that waits for text data: 'text', 'textarea', etc.
##How to Use
Rule editor added via twig variable ``attr`` with ``data-page-component-module': 'orofrontend/default/js/app/components/rule-editor-component``

    <div>{{ form_row(form.rule, {'attr': {
        'data-page-component-module': 'orofrontend/default/js/app/components/rule-editor-component',
        'data-page-component-options': {}
    }}) }}</div>
or 

    'subblocks': [{
        'title': '',
        'data': [
            form_row(form.productAssignmentRule, {'attr': {
                'data-page-component-module': 'orofrontend/default/js/app/components/rule-editor-component'
                'data-page-component-options': {}
            }})
        ]
    }]
##Options and validation
Autocompletion and validation cases added via json in ``data-page-component-options``:

    data-page-component-options': {
        entities: oro_product_expression_autocomplete_data(),
        allowedOperations: [],
        operations: {},
        allowedOperations: []
    }|json_encode

``entities`` is a data for autocompletion and verification. It has two keys ``root_entities`` and ``fields_data``.

``root_entities`` contains entities which are root of entities tree.

    "root_entities": {
        "Oro\Bundle\ProductBundle\Entity\Product": "product",
        "Oro\Bundle\PricingBundle\Entity\PriceList": "pricelist"
    }
    
``fields_data`` is a dictionary of entities.

This data received from server-side.

###Default options:
    operations: {
        math: ['+', '-', '%', '*', '/'],
        bool: ['and', 'or'],
        equality: ['=', '!='],
        compare: ['>', '<', '<=', '>=', 'like'],
        inclusion: ['in', 'not in']
    },
    allowedOperations: ['math', 'bool', 'compare', 'inclusion']

###How to customize
Developer can control the functions of autocompletion and validation via ``allowedOperations`` option:

    allowedOperations: ['math']

In this case, only math operations will be allowed for input.

##Helpers
If you have components that helps user select external data, you can use them via helper.

    dataSource: {
        pricelist: form_widget(priceListSelect)
    }
In this case widget ``priceListSelect`` used for handling entity ``pricelist`` to select specific pricelist. It adds pricelist's ``id`` field data to editor as a number in ``[]``: ``pricelist[2]``
