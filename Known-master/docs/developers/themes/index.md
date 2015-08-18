# Getting started writing Known themes

Themes allow you to easily extend, or provide alternatives to, Known's look and feel.

While plugins can also extend or replace Known's UI, in conjunction with extending or replacing its internal logic,
themes are a lightweight alternative. Whereas multiple plugins can be installed, only one theme can be enabled at any
one time.

Themes use standard [Known templates](../templating/index.md). It's a good idea to be familiar with the
core Known template concepts.

Themes are selected from the `Themes` section of the administration panel.

## Layout

The theme's folder must sit inside /Themes.

Each theme must have at least three things:

* theme.ini: a configuration file that provides information about the theme in the admin panel
* preview.png: a screenshot of the theme for use in the administration panel
* A set of templates under the theme's `templates` subfolder

## Structure of theme.ini

The theme.ini file contains a number of text entries::

    [Plugin description]
    name =              "The name of the theme"
    version =           "The theme's version number"
    author =            "Author's name"
    author_email =      "Author's email address"
    author_url =        "http://authors-url/"
    description =       "A short description that will be displayed in the administration panel"

More may be added over time. These entries are displayed in Known's administration panel but may also be displayed in
a future plugin directory.

## Extending themes

In addition, you can extend templates with entries in a special `[Extensions]` section. For example::

    [Extensions]
    "template/one" = "mytheme/template/one"
    "template/two" = "mytheme/template/two"

Here, any calls to `template/one` will also call `mytheme/template/one` with the same variables.

## Example

Let's say your theme is called PDX Carpet.

Your theme would live in the folder `/Themes/PDXCarpet/`, and contain a `plugin.ini` and `preview.png` file as described
above, to help describe the theme in the administration panel.

You will also need a `/Themes/PDXCarpet/templates/default/` folder.

You could overwrite the `shell/footer` theme simply by adding a new file called `/Themes/PDXCarpet/templates/default/shell/footer.tpl.php`. This could contain some simple text::

    <p>
        This is a custom <a href="https://withknown.com">Known</a> theme!
    </p>

To add a custom, static CSS file, you might want to `extend` the `shell/head` theme to reference it. To do ths, you would add an extension to your `theme.ini` file::

    [Extensions]
    "shell/head" = "pdxcarpet/shell/head"

You would then create a new template file in `/Themes/PDXCarpet/templates/default/pdxcarpet/shell/head.tpl.php` with some HTML to be injected into the page header::

    <link href="<?= \Idno\Core\site()->config()->url ?>Themes/PDXCarpet/css/default.css" rel="stylesheet">

Finally, you'd create a normal static CSS file in `/Themes/PDXCarpet/css/default.css`.

## Reference themes

Known ships with several reference themes. We recommend you take a look at the `Solo` theme to see how a simple alternative theme is put together.