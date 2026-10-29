<?php

/*
 * PHPacto - Contract testing solution
 *
 * Copyright (c) Damian Długosz
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace PHPacto\Recorder\Bin;

use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RecorderTest extends TestCase
{
    /**
     * @var vfsStream
     */
    protected $fs;

    public function setUp(): void
    {
        // Setup and cache the virtual file system
        $this->fs = vfsStream::setup('root', 0666);
    }

    public function test_it_creates_new_pacto_contract_from_real_request()
    {
        putenv('CONTRACTS_DIR=' . $this->fs->url());
        putenv('RECORDER_PROXY_TO=' . 'https://jsonplaceholder.typicode.com/');

        require __DIR__ . '/../../bin/recorder.php';

        self::assertTrue(true, 'No exeption should be thrown');
    }
}
