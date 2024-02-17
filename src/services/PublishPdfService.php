<?php
/**
 * @link https://abm.at
 * @copyright Copyright (c) abm Feregyhazy & Simon GmbH
*/

namespace abmat\publishpdf\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;

class PublishPdfService extends Component
{
    function handleIssuuException($exception): bool|string
    {
        Craft::error($exception->getCode() . " - " . $exception->getMessage(), 'publishpdfdebug');
        throw new \Exception($exception->getCode() . " - " . $exception->getMessage());
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
        return Craft::t('abm-publishpdf', 'Error');
    }

    /**
     * check if an asset is already uploaded to Yumpu
     */
    function isAssetUploaded(Asset $asset): ?string
    {
        $AssetRecord = $this->getAssetRecord($asset);
        if($AssetRecord == null) {
            return $this->formatResults(Craft::t('abm-publishpdf', '-'));
        } else if($AssetRecord->publisherState == 'progress') {
            if($this->checkAssetProgress($AssetRecord)) {
                //Todo: get new AssetRecord or not?
                //$AssetRecord = $this->getAssetRecord($asset);
                return '<a href="'.$AssetRecord->publisherUrl.'" title="Visit publisher url" rel="noopener" target="_blank" data-icon="world" aria-label="View"></a>';
            } else {
                return $this->formatResults(Craft::t('abm-publishpdf', 'processing'));
            }
        } else if($AssetRecord->publisherState == 'completed') {
            return '<a href="'.$AssetRecord->publisherUrl.'" title="Visit publisher url" rel="noopener" target="_blank" data-icon="world" aria-label="View"></a>';
        }
        return $this->formatResults(Craft::t('abm-publishpdf', '-'));
    }

    /**
     * format the result
     */
    function formatResults($string): string
    {
        return $string;
    }
}