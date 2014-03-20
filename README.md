SourceMod API docset
====================

Generates SourceMod API docset to use with Zeal or Dash.

    require_once 'autoloader.php';

    use Thekot\DocsetBuilder;

    $docset_builder = new DocsetBuilder(
        'SM_API',  // docset name
        'assets',  // assets dir
        'sourcemod_include',  // sourcemod includes dir
        'somewhere\zeal\docsets'  // docsets dir
    );
    $docset_builder->build();

Requires PHP 5.3 with enabled PDO sqlite.


