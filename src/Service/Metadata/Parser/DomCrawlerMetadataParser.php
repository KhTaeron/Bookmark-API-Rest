<?php

namespace App\Service\Metadata\Parser;

use Exception;
use Symfony\Component\DomCrawler\Crawler;

class DomCrawlerMetadataParser implements MetadataParserInterface
{

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     image: string,
     *     language: string
     * }
     */
    public function getMetadata(string $url, string $content): array
    {
            $crawler = new Crawler($content);

            $title = $crawler->filterXPath('//title')->text('');
            $description = $crawler->filterXPath('//meta[@name="description"]')->attr('content', '');
            $image = $crawler->filterXPath('//meta[@property="og:image"]')->attr('content', '');
            $language = $crawler->filterXPath('//html')->attr('lang', '');

            return [
                "title" => $title,
                "description" => $description,
                "image" => $image,
                "language" => $language
            ];

    }
}

