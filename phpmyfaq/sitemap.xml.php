<?php

/**
 * The dynamic Google Sitemap builder.
 *
 * https://[...]/sitemap.xml.php
 * https://[...]/sitemap.xml.php?gz=1
 * https://[...]/sitemap.xml
 * https://[...]/sitemap.gz
 * https://[...]/sitemap.xml.gz
 *
 * The Google Sitemap protocol is described here:
 * https://www.google.com/webmasters/sitemaps/docs/en/protocol.html
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package phpMyFAQ
 * @author Matteo Scaramuccia <matteo@phpmyfaq.de>
 * @copyright 2006-2022 phpMyFAQ Team
 * @license https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link https://www.phpmyfaq.de
 * @since 2006-06-26
 */

use phpMyFAQ\Date;
use phpMyFAQ\Faq;
use phpMyFAQ\Filter;
use phpMyFAQ\Helper\HttpHelper;
use phpMyFAQ\Link;
use phpMyFAQ\Strings;

const PMF_SITEMAP_GOOGLE_MAX_URLS = 50000;
const PMF_SITEMAP_GOOGLE_GET_GZIP = 'gz';
const PMF_SITEMAP_GOOGLE_FILENAME = 'sitemap.xml';
const PMF_SITEMAP_GOOGLE_FILENAME_GZ = 'sitemap.xml.gz';

const PMF_ROOT_DIR = __DIR__;
const IS_VALID_PHPMYFAQ = null;

//
// Bootstrapping
//
require __DIR__ . '/src/Bootstrap.php';

//
// Initializing static string wrapper
//
Strings::init();

if (false === $faqConfig->get('seo.enableXMLSitemap')) {
    exit();
}

// {{{ Functions
function buildSiteMapNode($location, $lastModified = null)
{
    if (empty($lastModified)) {
        $lastModified = Date::createIsoDate($_SERVER['REQUEST_TIME'], DATE_W3C, false);
    }
    if (preg_match('/^[1|2][0-9]{3}-[0|1][0-9]-[0|1|2|3][0-9]$/', $lastModified)) {
        $lastModified .= 'T' . date('H:i:sO');
    }
    if (preg_match('/^[1|2][0-9]{3}-[0|1][0-9]-[0|1|2|3][0-9]$/', $lastModified)) {
        $lastModified .= 'T' . date('H:i:sP');
    } elseif (preg_match('/([\+|\-][0-9]{2})([0-9]{2})$/', $lastModified, $arrayFind)) {
        if (isset($arrayFind[1]) && isset($arrayFind[2])) {
            $lastModified = str_replace($arrayFind[0], $arrayFind[1] . ':' . $arrayFind[2], $lastModified);
        }
    }
    return '<url>'
    . '<loc>' . Strings::htmlspecialchars($location) . '</loc>'
    . '<lastmod>' . $lastModified . '</lastmod>'
    . '</url>';
}

//
// Future improvements
// WHEN a User PMF Sitemap will be:
//   a. bigger than 10MB (!)
//   b. w/ more than 50K URLs (!)
// we'll manage this issue using a Sitemap Index Files produced by this PHP code
// including Sitemap URLs always produced by this same PHP code (see PMF_SITEMAP_GOOGLE_GET_INDEX)
//

$oFaq = new Faq($faqConfig);
// Load the faq
$items = $oFaq->getTopTenData(PMF_SITEMAP_GOOGLE_MAX_URLS - 1);
$visitsMax = 0;
$visitMin = 0;
if (count($items) > 0) {
    $visitsMax = $items[0]['visits'];
    $visitMin = $items[count($items) - 1]['visits'];
}

// Sitemap header
$siteMap =
    '<?xml version="1.0" encoding="UTF-8"?>'
    . '<urlset xmlns="http://www.google.com/schemas/sitemap/0.9"'
    . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
    . ' xsi:schemaLocation="http://www.google.com/schemas/sitemap/0.9'
    . ' http://www.google.com/schemas/sitemap/0.84/sitemap.xsd">';
// 1st entry: the faq server itself
$siteMap .= buildSiteMapNode(
    $faqConfig->getDefaultUrl(),
    Date::createIsoDate($_SERVER['REQUEST_TIME'], DATE_ISO8601, false)
);

// nth entry: each faq
foreach ($items as $item) {
    // a. We use plain PMF urls w/o any SEO schema
    $link = str_replace($_SERVER['SCRIPT_NAME'], '/index.php', $item['url']);
    // b. We use SEO PMF urls
    if (PMF_SITEMAP_GOOGLE_USE_SEO) {
        if (isset($item['thema'])) {
            $oL = new Link($link, $faqConfig);
            $oL->itemTitle = $item['thema'];
            $link = $oL->toString();
        }
    }
    $siteMap .= buildSiteMapNode($link, $item['date']);
}

$siteMap .= '</urlset>';

$getGzip = Filter::filterInput(INPUT_GET, PMF_SITEMAP_GOOGLE_GET_GZIP, FILTER_VALIDATE_INT);
if (!is_null($getGzip) && (1 == $getGzip)) {
    if (function_exists('gzencode')) {
        $sitemapGz = gzencode($siteMap);
        header('Content-Type: application/x-gzip');
        header('Content-Disposition: attachment; filename="' . PMF_SITEMAP_GOOGLE_FILENAME_GZ . '"');
        header('Content-Length: ' . strlen($sitemapGz));
        echo $sitemapGz;
    } else {
        $http = new HttpHelper();
        $http->setStatus(404);
    }
} else {
    header('Content-Type: text/xml');
    header('Content-Disposition: inline; filename="' . PMF_SITEMAP_GOOGLE_FILENAME . '"');
    header('Content-Length: ' . strlen($siteMap));
    echo $siteMap;
}

$faqConfig->getDb()->close();
