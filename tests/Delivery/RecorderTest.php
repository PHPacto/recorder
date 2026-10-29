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

namespace PHPacto\Recorder\Delivery;

use PHPacto\Factory\SerializerFactory;
use PHPacto\Logger\Logger;
use Http\Client\Exception\HttpException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Symfony\Component\Serializer\SerializerInterface;

class RecorderTest extends TestCase
{
    /**
     * @var vfsStream
     */
    protected $fs;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * @var Recorder
     */
    protected $controller;

    /**
     * @var string
     */
    protected $proxyTo;

    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $this->logger = $this->createMock(Logger::class);
        $this->serializer = SerializerFactory::getInstance();
        $this->proxyTo = 'http://proxied-host:8888/proxied-dir';

        // Setup and cache the virtual file system
        $this->fs = vfsStream::setup('root', 0666);

        $this->controller = new Recorder($this->client, $this->logger, $this->proxyTo, $this->fs->url());
    }

    /**
     * @cover MockProxyController
     */
    public function test_it_proxies_request_and_records_contract()
    {
        // A client wiil make a request like this
        $stream = new Stream('php://memory', 'rw');
        $request = new ServerRequest([], [], '/my-test-path?query=search', 'method', $stream, ['Content-Type' => 'text/html; charset=utf-8']);
        $stream->write('Request Body');

        // The proxied server will respond with
        $stream = new Stream('php://memory', 'rw');
        $response = new Response($stream, 418, ['Y' => 'RESPONSE HEADERS']);
        $stream->write('Response Body');

        $this->client->expects(self::once())
            ->method('sendRequest')
            ->with(self::callback(static function (RequestInterface $request) {
                self::assertEquals('proxied-host', $request->getUri()->getHost());
                self::assertEquals(8888, $request->getUri()->getPort());
                self::assertEquals('/proxied-dir/my-test-path', $request->getUri()->getPath());
                self::assertEquals('query=search', $request->getUri()->getQuery());
                self::assertEquals('text/html; charset=utf-8', $request->getHeaderLine('Content-Type'));

                return true;
            }))
            ->willReturn($response);

        $response = $this->controller->handle($request);

        // Assertions on Response coming from proxied server
        self::assertEquals(418, $response->getStatusCode());
        self::assertEquals('RESPONSE HEADERS', $response->getHeaderLine('Y'));
        self::assertEquals('Response Body', (string) $response->getBody());

        // Assert that a cantract is generated
        self::assertTrue($this->fs->hasChildren(), 'No contract has been recorded');

        // Assertions on recorded contract
        $contract = $this->fs->getChildren()[0]->getContent();

        // Contract Request
        self::assertStringContainsString('method: METHOD', $contract);
        self::assertStringContainsString('path: /my-test-path', $contract);
        self::assertStringContainsString("Content-Type:", $contract);
        self::assertStringContainsString("- text/html", $contract);
        self::assertStringContainsString("- charset=utf-8", $contract);
        self::assertStringContainsString("body:\n        _rule: equals\n        sample: 'Request Body'", $contract);

        // Contract Response
        self::assertStringContainsString('status_code: 418', $contract);
        self::assertStringContainsString("'Y': 'RESPONSE HEADERS'", $contract);
        self::assertStringContainsString("body:\n        _rule: equals\n        sample: 'Response Body'", $contract);
    }

    /**
     * @cover MockProxyController
     */
    public function test_it_proxies_request_and_records_contract_when_server_respond_with_error()
    {
        // A client will make a request like this
        $stream = new Stream('php://memory', 'rw');
        $request = new ServerRequest([], [], '/my-test-path', 'method', $stream, ['X' => 'REQUEST HEADERS']);
        $stream->write('Request Body');

        // The proxied server will respond with
        $stream = new Stream('php://memory', 'rw');
        $response = new Response($stream, 418, ['Y' => 'RESPONSE HEADERS']);
        $stream->write('Response Body');

        $this->client->expects(self::once())
            ->method('sendRequest')
        // Assertions on Request to being send to proxied server
            ->willThrowException(new HttpException('Server respond with a BAD status code', $request, $response));

        $response = $this->controller->handle($request);

        // Assertions on Response coming from proxied server
        self::assertEquals(418, $response->getStatusCode());
        self::assertEquals('RESPONSE HEADERS', $response->getHeaderLine('Y'));
        self::assertEquals('Response Body', (string) $response->getBody());

        // Assert that a cantract is generated
        self::assertTrue($this->fs->hasChildren(), 'No contract has been recorded');

        // Assertions on recorded contract
        $contract = $this->fs->getChildren()[0]->getContent();

        // Contract Request
        self::assertStringContainsString('method: METHOD', $contract);
        self::assertStringContainsString('path: /my-test-path', $contract);
        self::assertStringContainsString("X: 'REQUEST HEADERS'", $contract);
        self::assertStringContainsString("body:\n        _rule: equals\n        sample: 'Request Body'", $contract);

        // Contract Response
        self::assertStringContainsString('status_code: 418', $contract);
        self::assertStringContainsString("'Y': 'RESPONSE HEADERS'", $contract);
        self::assertStringContainsString("body:\n        _rule: equals\n        sample: 'Response Body'", $contract);
    }
}
