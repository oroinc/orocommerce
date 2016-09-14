FrontendNavigationBundle
===============

The `FrontendNavigationBundle` add ability to define frontend menus.

Example usage:

```
# DemoBundle\Resources\config\requirejs.yml

oro_menu_config:
    areas:                                    # menu area identifier
        frontend:                             # identifier area for menus using in frontend
            - top_nav                         # top navigation in frontend

    items:
        first_menu_item:
            label: 'First Menu Item'
            route: '#'
            extras:
                position: 10
        second_menu_item:
            label: 'Second Menu Item'
            route: '#'
            extras:
                position: 20

    tree:
        top_nav:
            children:
                first_menu_item ~
                second_menu_item ~
```

Please see [documentation](https://github.com/orocrm/platform/tree/master/src/Oro/Bundle/NavigationBundle/README.md) for more details.
