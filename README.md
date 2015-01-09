# EasyBib\Asset

Prefixing files with a content MD5, as a composer script.

### Does

* copy any file you want to a `[content-md5]-original-file-name.ext` file
* provide that filename to your app

### Does Not

* do anything else to your files (no minification, no nothing)

## Setup/Usage

1. `$ composer require easybib/asset-php 1.\*`

2. Add the needed configuration in your composer.json

For example:

```json
    "config": {
        "asset": {
            "sourcePath": "web",
            "targetPath": "dist",
            "files": ["/js/main.js", "/css/main.css"]
        }
    },
```
This will copy the files `web/js/main.js` to something like `dist/js/2132121abcf13223-main.js`,
and `web/css/main.css` to something like `dist/css/2132121abcf13223-main.js`.

3. Register it as some kind of script in your composer.json

For example, to let it run when you do `composer build`:

```json
    "scripts": {
        "build": "EasyBib\\Asset::run"
    },
```

Composer also has some [magic script names](https://getcomposer.org/doc/articles/scripts.md#event-names)
that it triggers automatically. The following would make it run after every `composer install`:
```json
    "scripts": {
        "post-install-cmd": "EasyBib\\Asset::run"
    },
```

4. In your app, replace the path to the file with a call to `EasyBib\Asset::path`.

For example, with the above configuration, the following
```php
    <script src="<?php EasyBib\\Asset::path('/js/main.js'); ?>"></script>
```
Will expand into
```php
    <script src="/js/123abcdwhatever-main.js"></script>
```
iff you ran `Easybib\Asset::run`. If you didn't run it, or if it doesn't know about that
file it will just return `/js/main.js`.
