# EasyBib\Asset

Prefixing files with a content MD5, as a composer script.

### Does

* copy any file you want to a `[content-md5]-original-file-name.ext` file
* provide that filename to your app

### Does Not

* do anything else to your files (no minification, no nothing)

## Setup/Usage

`$ composer require easybib/asset-php 1.\*`

### composer

The script exposes two composer "scripts" - `EasyBib\\Asset::run` and
`EasyBib\\Asset::clear`. You'll also need to add the configuration
to your `composer.json`.

For example:

```json
    "config": {
        "asset": {
            "sourcePath": "web",
            "targetPath": "dist",
            "files": ["/js/main.js", "/css/main.css"]
        }
    },
    "scripts": {
        "build": "EasyBib\\Asset::run",
        "clear-build": "EasyBib\\Asset::clear"
    },
```

You can choose other script names, of course. Composer also has some [magic
script names](https://getcomposer.org/doc/articles/scripts.md#event-names) that
it triggers automatically.

The `clear` command deletes the current mapping and will make the app fall back
to the source file.

### app

In your app, replace the path to the file with a call to `EasyBib\Asset::path`.

For example, with the above configuration, the following
```php
    <script src="<?php EasyBib\\Asset::path('/js/main.js'); ?>"></script>
```
Will expand into
```php
    <script src="/js/123abcdwhatever-main.js"></script>
```
once you ran `composer build`. It will fall-back to `/js/main.js` when you
ran `composer clear-build`.
