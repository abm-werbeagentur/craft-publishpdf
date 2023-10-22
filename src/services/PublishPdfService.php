<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\services;

use Craft;
use craft\base\Component;

class PublishPdfService extends Component
{
    function handleYumpuException($exception): bool|string
    {
        Craft::info('handleYumpuException', 'publishpdfdebug');
        Craft::info($exception, 'publishpdfdebug');
        $response = $exception->getResponse();
        $stream = $response->getBody();
        $contents = json_decode($stream->getContents());
        if(isset($contents->error)) {
            return $response->getStatusCode() . " - " . $contents->error;
        }
        return Craft::t('imhomedia-publishpdf', 'Error');
    }
}