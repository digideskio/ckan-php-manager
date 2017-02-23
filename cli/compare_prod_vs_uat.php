<?php
/**
 * @author Alex Perfilov
 * @date   3/30/14
 *
 */

namespace CKAN\Manager;


use EasyCSV\Reader;
use EasyCSV\Writer;

require_once dirname(__DIR__) . '/inc/common.php';

/**
 * Create results dir for logs
 */
$results_dir = CKANMNGR_RESULTS_DIR . date('/Ymd') . '_CHECK_PROD_VS_UAT';

if (!is_dir($results_dir)) {
    mkdir($results_dir);
}

echo 'prod.csv' . PHP_EOL;
if (!is_file($results_dir . '/prod.csv')) {
    $prod = new Writer($results_dir . '/prod.csv');

    $prod->writeRow([
        'title',
        'title_simple',
        'name',
        'url',
        'topics',
        'categories',
    ]);

    $ProdCkanManager = new CkanManager(CKAN_API_URL);
    $ProdCkanManager->resultsDir = $results_dir;

//    $prod_commerce = $ProdCkanManager->exportBrief('organization:(doc-gov OR bis-doc-gov OR mbda-doc-gov OR trade-gov OR census-gov ' .
//        ' OR eda-doc-gov OR ntia-doc-gov OR ntis-gov OR nws-doc-gov OR bea-gov OR uspto-gov)' .
//        ' AND -metadata_type:geospatial AND dataset_type:dataset AND -harvest_source_id:[\'\' TO *]');


//    https://catalog.data.gov/organization/nd-gov?harvest_source_title=North+Dakota+GIS+Hub+Data+Portal
    $prod_commerce = $ProdCkanManager->exportBrief('organization:nd-gov AND dataset_type:dataset' .
        ' AND harvest_source_title:North*');
    $prod->writeFromArray($prod_commerce);
} else {
    $prod = new Reader($results_dir . '/prod.csv');
    $prod_commerce = $prod->getAll();
}

echo 'uat.csv' . PHP_EOL;
if (!is_file($results_dir . '/uat.csv')) {
    $uat = new Writer($results_dir . '/uat.csv');

    $uat->writeRow([
        'title',
        'title_simple',
        'name',
        'url',
        'topics',
        'categories',
    ]);

    $UatCkanManager = new CkanManager(CKAN_UAT_API_URL);
    $UatCkanManager->resultsDir = $results_dir;

//    $uat_commerce = $UatCkanManager->exportBrief('extras_harvest_source_title:Commerce JSON', '',
//        'http://uat-catalog-fe-data.reisys.com/dataset/');

//    http://uat-catalog-fe-data.reisys.com/organization/test-org-082615?harvest_source_title=ND.gov+New+Data.json+HS

    $uat_commerce = $UatCkanManager->exportBrief('organization:test-org-082615 AND harvest_source_title:ND*', '',
        'http://uat-catalog-fe-data.reisys.com/dataset/');
    $uat->writeFromArray($uat_commerce);

} else {
    $uat = new Reader($results_dir . '/uat.csv');
    $uat_commerce = $uat->getAll();
}

$uat_commerce_by_title = [];

foreach ($uat_commerce as $name => $dataset) {
    $title = $dataset['title_simple'];

    $uat_commerce_by_title[$title] = isset($uat_commerce_by_title[$title]) ? $uat_commerce_by_title[$title] : [];
    $uat_commerce_by_title[$title][] = $dataset;
}

echo 'prod_vs_uat.csv' . PHP_EOL;
is_file($results_dir . '/prod_vs_uat_commerce.csv') && unlink($results_dir . '/prod_vs_uat_commerce.csv');
$csv = new Writer($results_dir . '/prod_vs_uat_commerce.csv');
$csv->writeRow([
    'Prod Title',
    'Prod URL',
    'Prod Topics',
    'Prod Categories',
    'Matched',
    'UAT Title',
    'UAT URL',
    'URL Match',
]);

foreach ($prod_commerce as $name => $prod_dataset) {
    if (isset($uat_commerce_by_title[$prod_dataset['title_simple']])) {
        foreach ($uat_commerce_by_title[$prod_dataset['title_simple']] as $uat_dataset) {
            $csv->writeRow([
                $prod_dataset['title'],
                $prod_dataset['url'],
                $prod_dataset['topics'],
                $prod_dataset['categories'],
                true,
                $uat_dataset['title'],
                $uat_dataset['url'],
                true,
            ]);
        }
        continue;
    }

    $csv->writeRow([
        $prod_dataset['title'],
        $prod_dataset['url'],
        $prod_dataset['topics'],
        $prod_dataset['categories'],
        false,
        '',
        '',
        false,
    ]);
}

// show running time on finish
timer();
