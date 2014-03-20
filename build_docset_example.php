<?php
require_once 'autoloader.php';

use Thekot\DocsetBuilder;

$docset_builder = new DocsetBuilder(
    'SM_API',  // docset name
    'assets',  // assets dir
    'sourcemod_include',  // sourcemod includes dir
    'build'  // build docsets dir
);
$docset_builder->build();
