<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Request;

trait GetRequestData
{
    private function getRequestData(Request $request): array
    {
        $data = [];

        if ($request->headers->get('Content-Type') === 'application/json') {
            $content = $request->getContent();

            $data = json_decode($content, true);

            if ($data === null) {
                throw new \InvalidArgumentException('Invalid JSON data.');
            }
        } else {
            $data = $request->request->all();
        }
        
        return $data;
    }
}
