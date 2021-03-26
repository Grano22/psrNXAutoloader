<?php
/**
 * PHP WPAutoloader psrNX tests
 */
require "vendor/autoload.php";
$mainLoader = new PsrNXLoader();
var_dump($mainLoader->resolve("Project\Classes\BaseTest"));
var_dump(PsrNXLoader::splitPathPartUndercore("_me_loves__soMuch"));
var_dump($mainLoader->resolve("Project\Classes\_BaseTest\Licho\Bacho"));

use Project\Classes\BaseTest as BaseTest;
use Project\Classes\SampleSecond as SampleSecond;

$happyClass = new BaseTest();
$secondHappyClass = new SampleSecond();
?>