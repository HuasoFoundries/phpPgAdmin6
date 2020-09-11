<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Php70\Rector\StaticCall\StaticCallOnNonStaticToInstanceCallRector;
use Rector\Set\ValueObject\SetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();

    $parameters->set(Option::AUTO_IMPORT_NAMES, true);

    $parameters->set(Option::SETS, [
        SetList::ACTION_INJECTION_TO_CONSTRUCTOR_INJECTION,
        SetList::ARRAY_STR_FUNCTIONS_TO_STATIC_CALL,
        SetList::CODE_QUALITY,
        SetList::PHP_53,
        SetList::PHP_54,
        SetList::PHP_56,
        SetList::PHP_70,
        SetList::PHP_71,
        SetList::PHP_72,
        SetList::PHPSTAN,
        SetList::PHPUNIT_CODE_QUALITY,
        SetList::SOLID,
    ]);
    $parameters->set(Option::SKIP, [
        Rector\SOLID\Rector\Property\AddFalseDefaultToBoolPropertyRector::class  => [
            // single file
            __DIR__ . '/src/classes/Connection.php',
            // or directory
            __DIR__ . '/src/database/databasetraits/HasTrait.php'
            
        ] 
    ]);
    $parameters->set(Option::PHP_VERSION_FEATURES, '7.2');
    $parameters->set(Option::ENABLE_CACHE, true);
    $parameters->set(Option::CACHE_DIR, __DIR__ . '/.build/rector');
    $parameters->set(Option::PATHS, [
        __DIR__ . '/src',
         //__DIR__ . '/tests'
         ]);
    $parameters->set(Option::EXCLUDE_RECTORS, [
       StaticCallOnNonStaticToInstanceCallRector::class,
    ]);
};