Getting Started
===============

Table of Contents
-----------------
 - [Overview](#overview)
 - [Main purposes](#main-purposes)
 - [Directory structure](#directory-structure)
 - [How to register bundles](#how-to-register-bundles)
 - [Examples](#examples)

Overview
--------

Multiple Applications Structure was developed for convenience of application developer to separate applications, optimize their configurations and performance.

Main purposes
-------------

Multiple Applications Structure may be used in situation, when used only one single application-server for all applications. There is no need to install all applications in the separate directories. Just use desired entry point for separate `virtual host`.
One common configuration for all application, one `vendor` directory and simplified development.

Also, it may be used in architecture with several application-servers, on each server is running the own application. Just select the desired entry point on single instance. That's it.

Directory structure
-------------------

Multiple Applications Structure provides the separated directories architecture for configure any of application. Each application extend the **common configuration** and have global `parameters.yml`.
In all structure exists only one `console` script. By default it runs for `admin` application.

In old structure global **config** is located in `app/config` directory:
```
├── app/
│   ├── attachment/
│   │   └── ...
│   ├── cache/
│   │   ├── dev/
│   │   │   └── ...
│   │   └── prod/
│   │       └── ...
│   ├── config/
│   │   ├── config.yml
│   │   ├── config_dev.yml
│   │   ├── config_prod.yml
│   │   ├── parameters.yml
│   │   ├── routing.yml
│   │   └── security.yml
│   ├── logs/
│   │   └── ...
│   └── Resources/
│       └── ...
├── src/
│   └── ...
├── vendor/
│   └── ...
└── web/
    ├── app.php
    └── app_dev.php
```

Now configuration of any application is divided to **two parts**. Such as **common** and **own** configuration parts.

**Common** is located in `app/common` directory.
```
└── app/
    └── common/
        ├── config.yml
        ├── config_dev.yml
        ├── config_prod.yml
        └── parameters.yml
```

**Own application** configuration is located in subdirectory of `app` with name like *own application name* and extends **common** configuration.
```
└── app/
    ├── admin/
    │   ├── config/
    │   │   ├── config.yml
    │   │   ├── config_dev.yml
    │   │   ├── config_prod.yml
    │   │   ├── routing.yml
    │   │   └── security.yml
    ├── frontend/
    │   └── config/
    │       └── ...
    ├── installer/
    │   └── config/
    │       └── ...
    └── tracking/
        └── config/
            └── ...
```

`Resources` directory of application (if it needed) should be located in directory at the same level with `config` directory.
```
└── app/
    ├── admin/
    │   ├── config/
    │   │   └── ...
    │   └── Resources/
    │       └── ...
    ├── frontend/
    │   ├── config/
    │   │   └── ...
    │   └── Resources/
    │       └── ...
    ├── installer/
    │   ├── config/
    │   │   └── ...
    │   └── Resources/
    │       └── ...
    └── tracking/
        ├── config/
        │   └── ...
        └── Resources/
            └── ...
```

Also, all of applications have own directory for storing `attachments`, `cache` and `logs`.
For these purposes there is a corresponding folder `var`, that located at the same level with directory `app` and contains all these directories inside.
Cache and log names are following the `application ~ underscore ~ environment` pattern.
```
├── app/
└── var/
    ├── attachments/
    ├── cache/
    │   ├── admin_prod/
    │   ├── admin_dev/
    │   ├── frontend_prod/
    │   ├── install_prod/
    │   └── tracking_prod/
    └── logs/
        ├── admin_dev.log
        ├── admin_prod.log
        └── ...
```

For any of separated application must be created own entry point, instead of `app.php` in old structure, that run application respectively.
The main purpose of this action - specify the name of application inside entry point. Entry point names are arbitrary and do not need to match the application name.
```
├── app/
│   └── ...
└── web/
    ├── admin.php
    ├── frontend.php
    ├── install.php
    └── tracking.php
```

Below are general schemes of the **Multiple Applications Structure**.
```
├── app/
│   ├── admin/
│   │   ├── config/
│   │   │   ├── config.yml
│   │   │   ├── config_dev.yml
│   │   │   ├── config_prod.yml
│   │   │   ├── routing.yml
│   │   │   └── security.yml
│   │   └── Resources/
│   │       └── ...
│   ├── common/
│   │   ├── config.yml
│   │   ├── config_dev.yml
│   │   ├── config_prod.yml
│   │   └── parameters.yml
│   ├── frontend/
│   │   ├── config/
│   │   │   └── ...
│   │   └── Resources/
│   │       └── ...
│   ├── installer/
│   │   ├── config/
│   │   │   └── ...
│   │   └── Resources/
│   │       └── ...
│   └── tracking/
│       ├── config/
│       │   └── ...
│       └── Resources/
│           └── ...
├── src/
│   └── ...
├── var/
│   ├── attachments/
│   │   └── ...
│   ├── cache/
│   │   ├── admin_prod/
│   │   ├── admin_dev/
│   │   ├── frontend_prod/
│   │   ├── install_prod/
│   │   └── tracking_prod/
│   └── logs/
│       ├── admin_dev.log
│       ├── admin_prod.log
│       └── ...
├── vendor/
│   └── ...
└── web/
    ├── admin.php
    ├── frontend.php
    ├── install.php
    └── tracking.php
```

How to register bundles
-----------------------

For auto-registering *bundles* and *exclusions* in applications are used the same approach as in the old structure (see DistributionBundle/README.md) with a small difference.
Default application named **admin** is used block `bundles` from bundles.yml. All other applications are used their own blocks named by next pattern: `bundles ~ underscore ~ environment`.

``` yml
bundles:
    - VendorName\Bundle\VendorBundle\VendorAnyBundle
    - MyName\Bundle\MyCustomBundle\MyNameCustomBundle
#   - ...
bundles_frontend:
    - VendorName\Bundle\VendorBundle\VendorAnyBundle
    - MyName\Bundle\MyCustomBundle\MyNameCustomBundle
#   - ...
```

Examples
--------

