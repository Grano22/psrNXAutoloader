# psrNXAutoloader by Grano22 Dev
psrNX Autoloader is designed to easy autoload classes in local projects, wordpress themes/plugins and other. 

## Examples
Global scope context:
example project path - C:\xampp\htdocs\testit\
autoloader path - C:\xampp\htdocs\testit\vendor\autoload.php
ROOTPATH - C:\xampp\htdocs\testit\
BASEPATH - C:\xampp\htdocs\testit\\\{Your including php files dir\} | default is includes\

Example namespaces:
\ - ROOTPATH - C:\xampp\htdocs\testit\
Plugin - BASEPATH - C:\xampp\htdocs\testit\{Your including php files dir\}
_myAwesomePefix\myEpicClass - myAwesomePefix.myEpicClass.php
__myAwesomePefix\myEpicClass - escaped prefix - _myAwesomePefix\myEpicClass.php

Default prefixes:
You can turn it off by set allowedPredefinedPrefixes option to false
Classes\exampleClass - class.exampleClass.php
Exceptions\exampleClass - exception.exampleClass.php