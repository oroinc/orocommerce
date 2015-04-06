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

Multiple Applications approach was developed for convenience of application developer to separate applications, 
optimize their configurations and performance.

Main purposes
-------------

Multiple Applications approach should be used in situation when several applications should share the same code source.
So there will be no need to install applications in a separate directories. Just specify and use desired entry point 
file (front controller) for each application. These application will have both common and separate parts 
of configuration, and will use the save `vendor` directory with source code.

Also, it may be used in architecture with several application-servers where each server runs the own application. 
In this case developer have to select the desired entry for each application.

Directory structure
-------------------

Multiple Applications approach provides the separated directories architecture to configure all applications. 
Each application extend the **common configuration** and uses global common `parameters.yml` file.
There is only one `console` script for all applications. By default it uses `admin` application.

In old structure global **config** directory is located in `app/config` directory, and *cache* and *logs* 
directories are in `app` directory (same as at regular Symfony application): 

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

Now configuration of any application is divided into **two parts** - **common** part and **application** own 
configuration part.

**Common** part is located in `app/common` directory. It includes general configuration that should be used for all
application, and file `parameters.yml` used to store common parameters (like DB connection, application hosts etc).

```
└── app/
    └── common/
        ├── config.yml
        ├── config_dev.yml
        ├── config_prod.yml
        └── parameters.yml
```

**Application** own configuration is located in subdirectory of `app` with name equals to application name 
that usually extends **common** configuration.

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

`Resources` directory of application (if it needed) should be located in directory at the same level with `config` 
directory.

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

Also, each application has its own directory for storing `attachments`, `cache` and `logs`.
For these purposes there is a corresponding folder `var`, that located at the same level with directory `app` 
and contains all these directories inside. Cache and log names are following the 
`<application_name>_<environment>` pattern. Main purpose of `var` directory is to provide one writable directory 
outside the `web` directory. 

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

Each application must have its own entry point (similar to `app.php` in old structure), that runs appropriate
application. The main purpose of this file - specify the name of application inside entry point. 
Entry point names are arbitrary and do not need to match the application name.

```
├── app/
│   └── ...
└── web/
    ├── admin.php
    ├── frontend.php
    ├── install.php
    └── tracking.php
```

Below are general schema of the **Multiple Applications** structure.

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

To register *bundles* and *exclusions* application uses almost the same approach as in the old structure 
(see DistributionBundle/README.md) with a small difference.
Default application named **admin** uses block `bundles` from bundles.yml, and all other applications 
use their own blocks named by the pattern `bundles_<application_name>`.

``` yml
# admin bundles, used for `admin` application
bundles:
    - VendorName\Bundle\AdminBundle\VendorAdminBundle
    - MyName\Bundle\MyAdminBundle\MyNameAdminBundle
#   - ...

# frontend bundles, used for `frontend` application (according to pattern `bundles_<application_name>`)
bundles_frontend:
    - VendorName\Bundle\FrontendBundle\VendorFrontendBundle
    - MyName\Bundle\MyFrontendBundle\MyNameFrontendBundle
#   - ...
```

Examples
--------

Let's imagine an abstract website that provides some kinds of services. It has two different interfaces: 
frontend (website for end-level customers) and backend (admin panel). These two applications use 
same database located on single server instance and some common bundles.

An application developer can use Multiple Applications approach for these two applications - they will be located 
in the same directory, will use similar config and common working directories. 
But in fact there will be two different applications and, just in case, they can be separated into two different 
server instances with minimum labor.
