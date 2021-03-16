<?php

declare(strict_types=1);

use PhpParser\Node\Scalar\EncapsedStringPart;
use Rector\CodeQuality\Rector\Concat\JoinStringConcatRector;
use Rector\CodingStyle\Rector\Encapsed\EncapsedStringsToSprintfRector;
use Rector\CodingStyle\Rector\FuncCall\VersionCompareFuncCallToConstantRector;
use Rector\CodingStyle\Rector\Property\AddFalseDefaultToBoolPropertyRector;
use Rector\CodingStyle\Rector\Switch_\BinarySwitchToIfElseRector;
use Rector\Core\Configuration\Option;
use Rector\Core\ValueObject\PhpVersion;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    //$parameters->set(Option::OPTION_DRY_RUN,true);

    $parameters->set(Option::SETS, [
        SetList::CODE_QUALITY
    ]);
    /*
        SetList::CODING_STYLE,
        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
        SetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
        SetList::PHP_53,
        SetList::PHP_54,
        SetList::PHP_56,
        SetList::PHP_70,
        SetList::PHP_71,
        SetList::PHP_72,
         SetList::PHPUNIT_CODE_QUALITY,
        
    ]);*/
//  
 
    $parameters->set(Option::SKIP, [
        VersionCompareFuncCallToConstantRector::class=>[  __DIR__ . '/src',],

        BinarySwitchToIfElseRector::class=>[  __DIR__ . '/src',],
        StaticCallOnNonStaticToInstanceCallRector::class=>[
            __DIR__ . '/src',
        ],
        AddFalseDefaultToBoolPropertyRector::class  => [
            // single file
            __DIR__ . '/src/classes/Connection.php',
            // or directory
            __DIR__ . '/src/database/databasetraits/HasTrait.php'
            
        ] 
    ]);
    $parameters->set(Option::PHPSTAN_FOR_RECTOR_PATH, __DIR__ . '/phpstan.neon');
    $parameters->set(Option::PHP_VERSION_FEATURES, PhpVersion::PHP_74);
    $parameters->set(Option::ENABLE_CACHE, true);
    $parameters->set(Option::CACHE_DIR, __DIR__ . '/.build/rector');
    $parameters->set(Option::PATHS, [
        //__DIR__ . '/src/translations',
        //__DIR__ . '/src',
        __DIR__ . '/src/controllers',
        __DIR__ . '/src/database',
        __DIR__ . '/src/decorators',
        __DIR__ . '/src/middleware',
        
        __DIR__ . '/src/classes',
         //__DIR__ . '/tests'
         ]);
 
        // register single rule
        $services = $containerConfigurator->services();
        $services->set(EncapsedStringsToSprintfRector::class);
        $services->set(JoinStringConcatRector::class);

};