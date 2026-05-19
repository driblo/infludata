<?php

declare(strict_types=1);

use Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

expect()->extend('toBeOk', fn () => $this->toBe('ok'));
