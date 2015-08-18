# Using config.ini to configure Known

Known is configured through config.ini, a simple file that is stored in the root of your Known installation.

Usually, config.ini is created automatically during installation. There is no need to create a config.ini file manually.
However, sometimes you may wish to add values here.

Config.ini files use the [INI file format](https://en.wikipedia.org/wiki/INI_file).

You can also use yourdomain.ini: for example, if your site was stored at yourdomain.com, you could have a supplemental
config.ini file at yourdomain.com.ini. This is most useful in combination with the **multitenant** setting.

## Example config.ini file

    database = 'MySQL'
    dbhost = 'localhost'
    dbname = 'database_name'
    dbpass = 'database_password'
    dbuser = 'known_db_user'
    uploadpath = '/Users/ben/Sites/known.dev/data/';

## Common config.ini directives

The following directives are most commonly used to configure Known.

**database**<br>
The database engine used by Known. eg, "MongoDB" or "MySQL".

**dbhost**<br>
The database host. This is most commonly "localhost".

**dbname**<br>
The database name. This is whatever you have configured your database server to use - for example, "known".

**dbpass**<br>
Your database password. Depending on your server configuration, this may not be required.

**dbstring**<br>
A database connection string. If you're using MongoDB in particular, you can use this to supply authentication
credentials instead of dbuser, dbpass and dbname.

**dbuser**<br>
Your database user.

**filesystem**<br>
The file system to use. This is most commonly "local", for local storage. Other plugins may allow for other options.
If you are using MongoDB, you can leave this option blank to use GridFS storage.

**uploadpath**<br>
The full path used by Known to upload files. This path must be writeable by the web server.

## Other config.ini directives

**alwaysplugins[]**<br>
Use this to force plugins to always be loaded. Plugins that are listed here will never appear in the plugins directory
in Site Configuration, and there will be no visible way to switch them off.

You may use this directive multiple times, for example:

    alwaysplugins[] = 'Webhooks'
    alwaysplugins[] = 'StaticPages'

**antiplugins[]**<br>
Lists plugins that can never be loaded, no matter what. Plugins that are listed here will never appear in the plugins
directory, and will never be loaded.

    antiplugins[] = 'Twitter'
    antiplugins[] = 'Facebook'

**directloadplugins[] (alpha)**<br>
A list of plugins that should be loaded from an external, explicitly specified location. These have a special format,
as follows:

    directloadplugins[MyPlugin] = '/path/to/MyPlugin'
    directloadplugins[AnotherPlugin] = '/path/to/AnotherPlugin'

Note that paths should not have a trailing slash. Plugins must have been designed to be loaded in this way.

**experimental**<br>
Triggers experimental, hidden functionality to be enabled. This is false by default.

    experimental = true

**multi_syndication**<br>
Triggers whether plugins should allow site users to connect more than one syndication account per service. This is true
by default.

    multi_syndication = false

**multitenant**<br>
Allows you to power more than one Known site from the same installation. If this is the case, Known will expect the
database name to be the site host. "www" is always stripped, so for example, if the website address was www.yourdomain.com,
Known would expect the database name to be yourdomain.com. In most cases, you should leave the default value here.

    multitenant = true

**prerequisiteplugins[]**<br>
A list of plugins that must be loaded before all other plugins. Like alwaysplugins, these will always be loaded, and
will not show up in the plugin list.

    prerequisiteplugins[] = 'FrameworkPlugin'
    prerequisiteplugins[] = 'AnotherLibraryPlugin'

**sessionname**<br>
The internal identifier used to store the user session.

    sessionname = 'knownsession'
