<?php 
$csv = file('UrlToHtml_NBCNews.csv');

foreach($csv as $line) {
    $line = str_getcsv($line);
    $array["/Users/rahul/solr-7.2.1/example/exampledocs/crawl_data/".$line[0]] = trim($line[1]);
}

var_dump($array);
?>