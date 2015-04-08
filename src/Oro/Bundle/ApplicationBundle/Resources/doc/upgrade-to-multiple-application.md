Upgrade to multiple application structure
=========================================

If you as developer want to update you single application to [multiple application structure](./Resources/doc/getting-started.md#directory-structure).
You need to do following actions:

1. Install or update application
--------------------------------
**1. If application is not installed yet.**
Run the `composer update` command
```
$ php composer.phar update
```
in section `application_host` add your application hosts.
For example:
```
application_host.admin ('http://localhost/admin.php'): http://your-site-name/admin.php
application_host.frontend ('http://localhost/'): http://your-site-name
application_host.install ('http://localhost/install.php'): http://your-site-name/install.php
application_host.tracking ('http://localhost/tracking.php'): http://your-site-name/tracking.php
```
Remove `app/config` folder:
```
$ rm -rf app/config
```

**2. If application already installed.**
Move `app/parameters.yml` to `common/parameters.yml`.
Add hosts settings to the end of file.
For example:
```
application_host.admin: 'http://your-site-name/admin.php'
application_host.frontend: 'http://your-site-name/frontend.php'
application_host.install: 'http://your-site-name/install.php'
application_host.tracking: 'http://your-site-name/tracking.php'
```
Remove `app/config` folder:
```
$ rm -rf app/config
```

3. Move your attachments
------------------------
Move your attachments from `app/attachments` to `var/attachments`:
```
$ cp -r app/attachments/* var/attachments
```

Then remove `app/attachments` directory:
```
$ rm -rf app/attachments
```

4. Remove cache and logs directories
------------------------------------
Now application log files are located in `var/logs/<application_name>_<application_env>/`.
If you want to save your old log files, please, make backup `app/logs` folder or move them from `app/logs` to appropriate folders.

For removing current directories on a UNIX system, run following command:
```
$ rm -rf app/cache app/logs
```

5. Update Oro platform
----------------------
Run `oro:platform:update` command
```
$ php app/console oro:platform:update --force
```

6. Make sure `var` directory is writable
-----------------------------------------
Make sure `var` directory is writable both by the web server and the command line user.
On a UNIX system, if your web server user is different from your command line user, you can use [this approach](http://symfony.com/doc/2.3/book/installation.html#book-installation-permissions)

7. Make changes into your web service configuration
---------------------------------------------------
Change your web server setting according application hosts in `common/parameters.yml`

Now you have separate entry points which you specify for each application.
Also you can [add your own application](./Resources/doc/add-new-application).

Optional changes
----------------
This part is not require. These changes are necessary if you made your own custom change in single application.

**1.`AppKernel` file was changed.**
If you made your custom change into `AppKernel` file coordinate them with new `AppKernel`.

**2.`DistributionKernel` was removed.**
Now used `AppKernel` with application `install`. If you made custom change with dist, file coordinate them with this approach.



