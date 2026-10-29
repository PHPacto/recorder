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

    protected $mockServerProcess;

    public function setUp(): void
    {
        // Setup and cache the virtual file system
        $this->fs = vfsStream::setup('root', 0666);
    }

    public function tearDown(): void
    {
        if (is_resource($this->mockServerProcess)) {
            $status = proc_get_status($this->mockServerProcess);
            if ($status['running']) {
                proc_terminate($this->mockServerProcess, 5);
                proc_close($this->mockServerProcess);
            }
        }
    }

    public function test_it_creates_new_pacto_contract_from_mocked_request()
    {
        $mockServerPort = 8889;

        putenv('CONTRACTS_DIR=' . $this->fs->url());
        putenv('RECORDER_PROXY_TO=http://127.0.0.1:' . $mockServerPort . '/');

        // Start mock server that responds to any GET request with valid JSON
        $mockServerScript = __DIR__ . '/../../tests/bin/mock_server.php';
        $descSpec = [
            0 => ['pipe', 'r'],
            1 => ['file', sys_get_temp_dir() . '/recorder_mock.log', 'w'],
            2 => ['file', sys_get_temp_dir() . '/recorder_mock.log', 'a'],
        ];
        $this->mockServerProcess = proc_open(
            sprintf('php %s %d', $mockServerScript, $mockServerPort),
            $descSpec,
            $pipes
        );

        if (!is_resource($this->mockServerProcess)) {
            self::fail('Failed to start mock server');
        }

        // Wait for mock server to be ready (don't use fsockopen - it consumes the single connection)
        usleep(500000);

        // Run recorder (laminas runner handles ONE request then exits)
        // The recorder creates a default CLI request and proxies it to RECORDER_PROXY_TO
        $recorderScript = __DIR__ . '/../../bin/recorder.php';
        require $recorderScript;

        // Verify contract was created
        $children = $this->fs->getChildren();
        self::assertNotEmpty($children, 'No contract was created');

        $contractFiles = array_filter($children, function ($child) {
            return strpos($child->getName(), '.yaml') !== false;
        });
        self::assertNotEmpty($contractFiles, 'No YAML contract file was created');

        // Verify contract content
        $contract = reset($contractFiles)->getContent();
        self::assertStringContainsString('method: CLI', $contract);
        self::assertStringContainsString('status_code: 200', $contract);
        self::assertStringContainsString('application/json', $contract);
    }
}
