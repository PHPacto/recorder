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

use Bigfoot\PHPacto\Factory\PactRequestFactory;
use Bigfoot\PHPacto\Factory\PactResponseFactory;
use Bigfoot\PHPacto\Factory\SerializerFactory;
use Bigfoot\PHPacto\Logger\Logger;
use Bigfoot\PHPacto\Pact;
use Bigfoot\PHPacto\PactInterface;
use Http\Client\Exception\HttpException;
use Http\Client\Exception\RequestException;
use Http\Factory\Discovery\HttpFactory;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;

class Recorder implements RequestHandlerInterface
{
    /**
     * @var ClientInterface
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var array
     */
    private $proxyTo;

    /**
     * @var string
     */
    private $contractsDir;

    public function __construct(ClientInterface $client, Logger $logger, string $proxyTo, string $contractsDir)
    {
        $this->client = $client;
        $this->logger = $logger;
        $this->proxyTo = parse_url($proxyTo);
        $this->contractsDir = $contractsDir;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $response = $this->makeProxyCall($request);

        $pactResponse = PactResponseFactory::createFromPSR7($response);

        $pactRequest = PactRequestFactory::createFromPSR7($request);

        $time = time();

        $pact = new Pact($pactRequest, $pactResponse, sprintf('Created for you by PHPacto Recorder - %s', date('Y-m-d H:i:s', $time)));

        $this->writeContractFile($pact, $time);

        $response = $pactResponse->getSample();

        return $response;
    }

    private function makeProxyCall(ServerRequestInterface $request): ResponseInterface
    {
        $realUri = $this->getProxiedUri($request->getUri());

        try {
            // Proxy the HTTP request
            return $this->client->sendRequest($request->withUri($realUri));
        } catch (RequestException|ClientExceptionInterface $e) {
            if ($e instanceof HttpException) {
                return $e->getResponse();
            }

            throw $e;
        }
    }

    private function writeContractFile(PactInterface $pact, int $timestamp): void
    {
        $filename = sprintf('%s/%s.yaml', $this->contractsDir, date('YmdHis', $timestamp));

        $serializer = SerializerFactory::getInstance();
        file_put_contents($filename, $serializer->serialize($pact, 'yaml'));

        $this->logger->log('Contract wrote to ' . realpath($filename));
    }

    private function getProxiedUri(UriInterface $uri): UriInterface
    {
        return HttpFactory::uriFactory()->createUri()
            ->withScheme($this->proxyTo['scheme'] ?? 'http')
            ->withHost($this->proxyTo['host'] ?? 'localhost')
            ->withPort($this->proxyTo['port'] ?? ('https' === @$this->proxyTo['scheme'] ? 443 : 80))
            ->withPath(str_replace('//', '/', $this->proxyTo['path'] ?? '/') . $uri->getPath())
            ->withQuery($uri->getQuery())
            ->withFragment($uri->getFragment());
    }
}
