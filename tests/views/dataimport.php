<?php

/**
 * PHPPgAdmin v6.0.0-RC9
 */

function dataimportFactory($container)
{
    return new \PHPPgAdmin\Controller\DataimportController($container);
}
