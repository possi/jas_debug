jas Debug-Package
=========

Provides a small package of debugging utilites, especially a better var_dump-alternative

Installation:
-------------
Add this line to your composer.json "require" section:

### composer.json
```json
    "require": {
       ...
       "jas/debug": "*"
```

Usage
-----

Even without xdebug you now can output a smart display of any PHP-Value.
```php
jas\debug\Dumper::dump($var/*, ...*/);
```

You can pass as much arguments to the Dump-Method as you like. By default it outputs an HTML-Version of the PHP-Object.
When you pass an int bitmask (see Dumper-Constants) as last parameter, you are able to get a Text-Only version (with or
without <pre> around) as output or returned.


On-The-Fly usage
----------------
You may use this package on-the-fly via this Gist: https://gist.github.com/possi/5792653
```php
eval('?'.'>'.file_get_contents('https://gist.github.com/possi/5792653/raw'));
jas_dump($this, new stdClass(), array('foo' => 'bar'));
```

Planned Features
-----
* Test special dumper for SugarCRMs SugarBean
* Add the ErrorHandler
* especially with BackTrace-printer