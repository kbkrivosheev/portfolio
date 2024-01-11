<?php

declare(strict_types=1);

namespace app\Features\Converters\Pdf\ToHtml\Helpers;

use app\Features\Converters\Common\Exceptions\ConversionException;
use DOMDocument;
use DOMXPath;
use RuntimeException;
use yii\helpers\HtmlPurifier;
use yii\helpers\Json;

final class Html
{
    public static function getStyleJson(string $html): string
    {
        $styleCode = [];
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadHTML($html, LIBXML_NOERROR);
        $styleElements = $dom->getElementsByTagName('style');
        foreach ($styleElements as $style) {
            $styleCode[] = $style->nodeValue;
        }
        return Json::encode($styleCode);
    }

    /**
     * @throws RuntimeException
     */
    public static function purgeHtml(string $html): string
    {
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadHTML($html, LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);
        $elements = $xpath->query('//img');
        foreach ($elements as $element) {
            $element->parentNode->removeChild($element);
        }
        $newHtml = $dom->saveHTML();
        if ($newHtml === false) {
            throw new RuntimeException('Не удалось очистить html');
        }
        return $newHtml;
    }


    /**
     * @throws ConversionException
     */
    public static function getPurifiedHtml(string $html): string
    {
        try {
            $style = self::getStyleJson($html);
            $purHtml = HtmlPurifier::process($html, static function ($config) {
                $config->set('Attr.AllowedFrameTargets', ['_blank' => true]);
                $config->set('HTML.TargetBlank', true);
                $def = $config->getHTMLDefinition(true);
                $def->addAttribute('div', 'id', 'Text');
                $def->addAttribute('div', 'data-page-no', 'Text');
            });
            return self::addStyle($purHtml, $style);
        } catch (\DOMException $e) {
            throw new ConversionException('Не удалось очистить html ' . $e->getMessage());
        }
    }

    private static function addStyle(string $decisionHtml, string $decisionStyle): ?string
    {
        $decisionStyleArray = Json::decode($decisionStyle);
        $dom = new DOMDocument("1.0", "utf-8");
        $dom->loadHTML('<?xml encoding="UTF-8">' . $decisionHtml, LIBXML_NOERROR);
        foreach ($decisionStyleArray as $style) {
            try {
                $newStyleElement = $dom->createElement('style');
                $newStyleElement->setAttribute('type', 'text/css');
                $newStyleElement->nodeValue = $style;
                $dom->appendChild($newStyleElement);
            } catch (\DOMException $e) {
                continue;
            }
        }
        $html = $dom->saveHTML();
        return $html === false ? null : $html;
    }
}
