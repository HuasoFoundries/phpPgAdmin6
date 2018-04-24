#!/usr/bin/env php
<?php

/**
 * Convert SimpleTest tests to PHPUnit, mocks to Mockery.
 *
 * Caveats:
 * - Assertion + return value can't be combined automatically
 */

// filter down to arguments
$args = preg_grep('/^-[dhom]/', array_slice($argv, 1), PREG_GREP_INVERT);

// show help
if (in_array('-h', $argv) || in_array('--help', $argv)) {
    echo "\nusage: $argv[0] [-o] [-d]\n\n";
    echo " -o       : output changes only, don't update file\n";
    echo " -d       : shows a diff of changes that would be made, implies -o\n\n";
    echo " -m       : convert mocks\n\n";
    exit(1);
}

// handle diff output
if (in_array('-d', $argv)) {
    passthru('php ' . __FILE__ . " $argv[1] -o | diff -u {$argv[1]} -");
    exit(0);
}

$php = file_get_contents($argv[1]);

// ----------------------------
// Convert Mockery

if (in_array('-m', $argv)) {
    $patterns = [
        '/->setReturn(?:Value|Reference)\((.+?),\s*(.+?)\);/ims' => '->shouldReceive(\1)->andReturn(\2);',
        '/->throwOn\((.+?),(.+?)\);/ims'                         => '->shouldReceive(\1)->andThrow(\2);',
        '/->expectOnce\(([^,\)]+)\);/ims'                        => '->shouldReceive(\1)->once();',
        '/->expectNever\(([^,\)]+)\);/ims'                       => '->shouldReceive(\1)->never();',
        '/->expectOnce\((.+?),\s*array\((.+?)\)\s*\);/ims'       => '->shouldReceive(\1)->with(\2)->once();',
        '/->expectCallCount\((.+?),\s*(.+?)\)/ims'               => '->shouldReceive(\1)->times(\2);',
        '/new ([a-z_]*Mock[a-z_]+)\([^\)]*\)/ims'                => 'Mockery::mock()',
        '/new ([a-z_]*Mock[a-z_]+)\;/ims'                        => 'Mockery::mock();',
        '/Mock::generate[^;]+;\s*/'                              => '',
        '/new AnythingExpectation\(\)/'                          => '\Mockery::any()',
    ];

    foreach ($patterns as $from => $to) {
        $php = preg_replace($from, $to, $php);
    }
}

// ----------------------------
// Convert SimpleTest classes and assertions

$patterns = [
    '/assertEquals?/'                         => 'assertEquals',
    '/assertNotEquals?/'                      => 'assertNotEquals',
    '/assertPattern/'                         => 'assertRegExp',
    '/assertIdentical/'                       => 'assertSame',
    '/assertNotIdentical/'                    => 'assertNotSame',
    '/assertNoPattern/'                       => 'assertNotRegExp',
    '/assertReference/'                       => 'assertSame',
    '/assertIsA\((.+?),\s*(.+?)(,\s*.+?)?\)/' => 'assertInstanceOf(\2, \1)',
    '/expectException/'                       => 'setExpectedException',
    '/$this->pass()/'                         => '$this->assertTrue(true)',
    '/\bUnitTest(Case)?\b/'                   => 'PHPUnit_Framework_TestCase',
    '/setup/'                                 => 'setUp',
];

foreach ($patterns as $from => $to) {
    $php = preg_replace($from, $to, $php);
}

// ----------------------------
// Output

if (in_array('-o', $argv)) {
    echo $php;
} else {
    if (file_get_contents($argv[1]) != $php) {
        echo "updating {$argv[1]}\n";
        file_put_contents($argv[1], $php);
    }
}
