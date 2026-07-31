<?php

//define('__ROOT__', realpath(__DIR__ . '/../'));
//define('__WWW__', realpath(__DIR__ . '/../htdocs'));

// All three test classes write into tests/support/working and empty it again in
// tearDown(), so it has to exist before the first test runs - but because it is
// only ever empty, git cannot track it, and it is absent from a fresh clone.
// That is why every filesystem test errored in CI while passing locally, on a
// checkout where an earlier run had already left the directory behind.
//
// Created here rather than in each setUp() so there is one copy of it, and so
// it is in place no matter which test class runs first.
$working = __DIR__ . '/tests/support/working';

if (!is_dir($working)) {
    mkdir($working, 0777, true);
}
