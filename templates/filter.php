<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

use Toristy\Helpers\Parses\FilterParse;

get_header();
$filter = new FilterParse([
    "toristy-location" => get_query_var('toristy-location'),
    "toristy-city" => get_query_var('toristy-city'),
    "toristy-category" => get_query_var('toristy-category'),
    "toristy-type" => get_query_var('toristy-type'),
    "toristy-filter" => get_query_var('toristy-filter')
]);
$categories = $filter->GetCategories();
$types = $filter->GetTypes();
 list($services, $ser) = $filter->GetServices();
?>
    <div id='toristy-main' class="filter row row-large row-divided">
        <div class="toristy-main-area">
            <nav class="toristy-breadcrumb">
                <?php echo $filter->Breadcrumb(); ?>
            </nav>
            <!--section class="toristy-filter-title">
                <h1><?php //echo $filter->GetTitle(); ?></h1>
            </section-->
            <section class="toristy-category-wrap category-<?php echo count($categories); ?>">
                <div class="toristy-category-pedal left"></div>
                <div class="toristy-category">
                    <div class="toristy-category-inner">
                        <?php echo implode('', $categories); ?>
                    </div>
                </div>
                <div class="toristy-category-pedal right"></div>
            </section>
            <section class="toristy-category-wrap type category-<?php echo count($types); ?>">
                <div class="toristy-category-pedal left"></div>
                <div class="toristy-category">
                    <div class="toristy-category-inner">
                        <?php echo implode('', $types); ?>
                    </div>
                </div>
                <div class="toristy-category-pedal right"></div>
            </section>
            <section class="toristy-main-contents">
                <aside class="toristy-filter"><?php echo $filter->GetFilter(); ?></aside>
                <section class="toristy-filtered">
                    <div class="toristy-items<?php echo $ser; ?>"><?php echo $services; ?></div>
                    <?php echo $filter->GetPagination(); ?>
                </section>
            </section>
        </div>
    </div>
<?php

get_footer();