<?php

/**
 * Frontend for search log statistics.
 *
 * This Source Code Form is subject to the terms of the Mozilla Public License,
 * v. 2.0. If a copy of the MPL was not distributed with this file, You can
 * obtain one at https://mozilla.org/MPL/2.0/.
 *
 * @package   phpMyFAQ
 * @author    Anatoliy Belsky <anatoliy.belsky@mayflower.de>
 * @author    Thorsten Rinne <thorsten@phpmyfaq.de>
 * @copyright 2003-2022 phpMyFAQ Team
 * @license   https://www.mozilla.org/MPL/2.0/ Mozilla Public License Version 2.0
 * @link      https://www.phpmyfaq.de
 * @since     2003-03-30
 */

use phpMyFAQ\Filter;
use phpMyFAQ\Pagination;
use phpMyFAQ\Search;
use phpMyFAQ\Strings;
use phpMyFAQ\Translation;

if (!defined('IS_VALID_PHPMYFAQ')) {
    http_response_code(400);
    exit();
}
?>
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
  <h1 class="h2">
    <i aria-hidden="true" class="fa fa-tasks"></i>
      <?= Translation::get('ad_menu_searchstats') ?>
  </h1>
  <div class="btn-toolbar mb-2 mb-md-0">
    <div class="btn-group mr-2">
      <a class="btn btn-sm btn-danger" href="?action=truncatesearchterms&csrf=<?= $user->getCsrfTokenFromSession() ?>">
        <i aria-hidden="true" class="fa fa-trash"></i> <?= Translation::get('ad_searchterm_del') ?>
      </a>
    </div>
  </div>
</div>

        <div class="row">
          <div class="col-lg-12">
<?php
if ($user->perm->hasPermission($user->getUserId(), 'viewlog')) {
    $perPage = 10;
    $pages = Filter::filterInput(INPUT_GET, 'pages', FILTER_VALIDATE_INT);
    $page = Filter::filterInput(INPUT_GET, 'page', FILTER_VALIDATE_INT, 1);
    $csrfToken = Filter::filterInput(INPUT_GET, 'csrf', FILTER_UNSAFE_RAW);

    if (!isset($_SESSION['phpmyfaq_csrf_token']) || $_SESSION['phpmyfaq_csrf_token'] !== $csrfToken) {
        $csrfChecked = false;
    } else {
        $csrfChecked = true;
    }

    $search = new Search($faqConfig);

    if ($csrfChecked && 'truncatesearchterms' === $action) {
        if ($search->deleteAllSearchTerms()) {
            printf('<p class="alert alert-success">%s</p>', Translation::get('ad_searchterm_del_suc'));
        } else {
            printf('<p class="alert alert-danger">%s</p>', Translation::get('ad_searchterm_del_err'));
        }
    }

    $searchesCount = $search->getSearchesCount();
    $searchesList = $search->getMostPopularSearches($searchesCount + 1, true);

    if (is_null($pages)) {
        $pages = round((count($searchesList) + ($perPage / 3)) / $perPage, 0);
    }

    $start = ($page - 1) * $perPage;
    $end = $start + $perPage;

    $baseUrl = sprintf(
        '%sadmin/?action=searchstats&amp;page=%d',
        $faqConfig->getDefaultUrl(),
        $page
    );

    // Pagination options
    $options = [
        'baseUrl' => $baseUrl,
        'total' => count($searchesList),
        'perPage' => $perPage,
        'pageParamName' => 'page',
    ];
    $pagination = new Pagination($faqConfig, $options);
    ?>
          <div id="ajaxresponse"></div>
          <table class="table table-striped align-middle">
            <thead>
            <tr>
              <th><?= Translation::get('ad_searchstats_search_term') ?></th>
              <th><?= Translation::get('ad_searchstats_search_term_count') ?></th>
              <th><?= Translation::get('ad_searchstats_search_term_lang') ?></th>
              <th colspan="2"><?= Translation::get('ad_searchstats_search_term_percentage') ?></th>
              <th>&nbsp;</th>
            </tr>
            </thead>
            <tfoot>
            <tr>
              <td colspan="6"><?= $pagination->render() ?></td>
            </tr>
            </tfoot>
            <tbody>
    <?php
    $counter = $displayedCounter = 0;
    $self = substr(__FILE__, strlen($_SERVER['DOCUMENT_ROOT']));

    foreach ($searchesList as $searchItem) {
        if ($displayedCounter >= $perPage) {
            ++$displayedCounter;
            continue;
        }

        ++$counter;
        if ($counter <= $start) {
            continue;
        }
        ++$displayedCounter;

        $num = round(($searchItem['number'] * 100 / $searchesCount), 2);
        ?>
              <tr id="row-search-id-<?= $searchItem['id'] ?>">
                  <td><?= Strings::htmlspecialchars($searchItem['searchterm']) ?></td>
                  <td><?= $searchItem['number'] ?></td>
                  <td><?= $languageCodes[Strings::strtoupper($searchItem['lang'])] ?></td>
                  <td><meter max="100" value="<?= $num ?>"></td>
                  <td><?= $num ?>%</td>
                  <td>
                      <button class="btn btn-danger pmf-delete-search-term" href="#"
                              title="<?= Translation::get('ad_news_delete') ?>"
                              data-delete-search-term-id="<?= $searchItem['id'] ?>"
                              data-csrf-token="<?= $user->getCsrfTokenFromSession() ?>">
                        <i aria-hidden="true" class="fa fa-trash"
                           data-delete-search-term-id="<?= $searchItem['id'] ?>"
                           data-csrf-token="<?= $user->getCsrfTokenFromSession() ?>"></i>
                      </button>
                  </td>
              </tr>
        <?php
    }
    ?>
            </tbody>
          </table>
    <?php
} else {
    echo Translation::get('err_NotAuth');
}
?>
        </div>
      </div>
