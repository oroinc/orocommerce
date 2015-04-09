How to add new application
==========================

To add new application developer has to do four simple steps.


###1. Add application entry points to `web` directory

For example developer wants to add application called *someapplication* - in this case he has to add 
`someapplication.php` for production entry point and `someapplication_dev.php` for development entry point into `web`
directory. Developer can look at existing entry points pairs (`admin.php` and `admin_dev.php` or 
`frontend.php` and `frontend_dev.php`) to see how its made. Also developer have to set application name to kernel 
object.

```php
$kernel->setApplication('someapplication');
```

###2. Add application configuration to `app` directory

Developer has to add folder called same as the application to `app` directory with configuration files 
for new application with the following structure:


```
|── someapplication/
│   ├── config/
│   │   ├── config_dev.yml
│   │   ├── config_prod.yml
│   │   ├── config_test.yml
│   │   ├── config.yml
│   │   ├── routing_dev.yml
│   │   ├── routing_prod.yml
│   │   ├── routing_test.yml
│   │   ├── routing.yml
│   │   ├── security_dev.yml
│   │   ├── security_prod.yml
│   │   ├── security_test.yml
│   │   └── security.yml
|   └── Resources/
```

Developer can import common configs from `app/common` directory, each application must have its own configuration.
General resources (views, styles, js etc) for your application should be placed into `someapplication/Resources`
directory.


###3. Add application bundles to `oro/bundles.yml`

Developer has to add section with name `bundles_someapplication node` to load required bundle. It can be done in file
`oro/bundles.yml` in any bundle. 

```yml
bundles_someapplication:
    - { name: Symfony\Bundle\FrameworkBundle\FrameworkBundle }
    - { name: Symfony\Bundle\SecurityBundle\SecurityBundle }
    - { name: Symfony\Bundle\TwigBundle\TwigBundle }
    - { name: Symfony\Bundle\MonologBundle\MonologBundle }
    - { name: Symfony\Bundle\SwiftmailerBundle\SwiftmailerBundle }
    - { name: Symfony\Bundle\AsseticBundle\AsseticBundle }
    - { name: Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle }
    - { name: Doctrine\Bundle\DoctrineBundle\DoctrineBundle }
    - { name: Oro\Bundle\ApplicationBundle\OroApplicationBundle, priority: 10 }
```

OroApplicationBundle contains common functionality for all bundles and it is strongly recommended to load this bundle.
It used to organize interaction between different applications. For example you can create link from you 
someapplication to frontend or admin application which already available in the system.


###4. Launch application

Finally developer can set up virtual host (if required) and enter new application from browser (e.g. using URL
http://localhost/someapplication.php).
