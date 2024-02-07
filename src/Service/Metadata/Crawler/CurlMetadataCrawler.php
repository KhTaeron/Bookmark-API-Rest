<?php

namespace App\Service\Metadata\Crawler;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(id: 'metadataCurl', public: true)]
class CurlMetadataCrawler implements MetadataCrawlerInterface
{
    /**
     * @return array{
     *     contentType: string,
     *     content: string,
     *     statusCode: int
     * }
     */
    public function getContent(string $url): array {
        $ch = curl_init($url);

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);

        if ($response === false) {
            $errorMessage = 'Erreur cURL:' . curl_error($ch);
            return [$errorMessage];
        } ;
        
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);

        curl_close($ch);

        return [
            'contentType' => $contentType ?? '',
            'content' => $response ?? '',
            'statusCode' => $statusCode ?? 0,
        ];
    }

    /**
     *
     * @param string $headers 
     * @param string $headerName 
     * @return string|null
     */
    private function getHeaderValue(string $headers, string $headerName): ?string {
        $pattern = '/^' . preg_quote($headerName, '/') . ': (.+)$/im';

        if (preg_match($pattern, $headers, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }

}
