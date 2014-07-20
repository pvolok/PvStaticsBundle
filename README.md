PvStaticsBundle
===============

Installation
------------

To `composer.json`:


```json
{
    "require": {
        "pv/statics-bundle": "*@dev"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:pvolok/PvStaticsBundle.git"
        }
    ]
}
```

To `AppKernel`:

```php
new Pv\StaticsBundle\PvStaticsBundle(),
```


Common Principles
-----------------

### Paths

Paths must be specified by two ways:

1. Absolute: `@MyCoolBundle/js/file.js`
2. Relative: `./dir/file.js`


File Types
----------

### JavaScript

Include:

```javascript
// #include './file.js';
// #include './tpl.soy';
// #include './style.less';
```

Depend (files included in this files will not be included in current):

```javascript
// #depend '@MySiteBundle/js/global.js';
// #depend '@MySiteBundle/js/global.less';
```

### Less

Include:

```less
@import './bootstrap.less';
```

### Soy

Only standard stuff.

### Sprites

TBD
