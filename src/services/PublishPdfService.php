<?php
/**
 * @link https://www.imhomedia.at
 * @copyright Copyright (c) Imhomedia
*/

namespace imhomedia\publishpdf\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;

class PublishPdfService extends Component
{
    function handleIssuuException($exception): bool|string
    {
        Craft::info('handleIssuuException', 'publishpdfdebug');
        Craft::info($exception, 'publishpdfdebug');
        return false;
    }
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

    /**
     * check if an asset is already uploaded to Yumpu
     */
    function isAssetUploaded(Asset $asset): ?string
    {
        $AssetRecord = $this->getAssetRecord($asset);
        if($AssetRecord == null) {
            return $this->formatResults(Craft::t('imhomedia-publishpdf', '-'));
        } else if($AssetRecord->publisherState == 'progress') {
            if($this->checkAssetProgress($AssetRecord)) {
                //Todo: get new AssetRecord or not?
                //$AssetRecord = $this->getAssetRecord($asset);
                return '<a href="'.$AssetRecord->publisherUrl.'" title="Visit publisher url" rel="noopener" target="_blank" data-icon="world" aria-label="View"></a>';
            } else {
                return $this->formatResults(Craft::t('imhomedia-publishpdf', 'processing'));
            }
        } else if($AssetRecord->publisherState == 'completed') {
            return '<a href="'.$AssetRecord->publisherUrl.'" title="Visit publisher url" rel="noopener" target="_blank" data-icon="world" aria-label="View"></a>';
        }
        return $this->formatResults(Craft::t('imhomedia-publishpdf', '-'));
    }

    /**
     * format the result
     */
    function formatResults($string): string
    {
        return $string;
    }
}