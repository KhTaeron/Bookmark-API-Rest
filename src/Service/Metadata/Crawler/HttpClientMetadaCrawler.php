<?php

namespace App\Service\Metadata\Crawler;

use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsAlias(id: 'metadataHttp', public: true)]
class HttpClientMetadaCrawler implements MetadataCrawlerInterface
{
    private $httpClient;

    public function __construct(HttpClientInterface $httpClient)
    {
        $this->httpClient = $httpClient;
    }
    /**
     * @return array{
     *     contentType: string,
     *     content: string,
     *     statusCode: int
     * }
     */
    public function getContent(string $url): array
    {

        $response = $this->httpClient->request('GET', $url);
        $content = $response->getContent();
        $contentType = $response->getHeaders()['content-type'][0] ?? 'unknown';
        $statusCode = $response->getStatusCode();

        return [
            'contentType' => $contentType ?? '',
            'content' => $content ?? '',
            'statusCode' => $statusCode ?? 0,
        ];
    }

    /**
     *
     * @param string $headers 
     * @param string $headerName 
     * @return string|null
     */
    private function getHeaderValue(string $headers, string $headerName): ?string
    {
        $pattern = '/^' . preg_quote($headerName, '/') . ': (.+)$/im';

        if (preg_match($pattern, $headers, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

}
