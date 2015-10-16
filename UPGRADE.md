UPGRADE NOTES
=============

Upgrade from 1.0.0-alpha.1 to 1.0.0-alpha.2
-------------------------------------------

- `backend_prefix` parameter name was changed to `web_backend_prefix` - parameter name should be changed 
in `app/config/parameters.yml` and after that `php app/console cache:clear` command should be executed,
also need to fix name of this parameter in all custom cases (e.g. in customizations)
