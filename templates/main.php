<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

use Toristy\Helpers\Parses\HomeParse;

$home = new HomeParse();
$search = '';
$featured = $home->Featured();
$isFeature = (!empty($featured['services'])) ? ' show' : '';
$recommended = $home->Recommended();
$isRecommend = (!empty($recommended['services'])) ? ' show' : '';
$locations = $home->Locations();
$isLocation = (!empty($locations)) ? ' show' : '';
$randoms = [];//$home->Randoms();
$isRandom = (!empty($randoms)) ? ' show' : '';
$image = $home->GetImage();
$imgStyle = '';
if (strlen($image) > 0) {
    $imgStyle =  "background-image: url(".$image.")";
}
list($infoOne, $infoTwo) = $home->GetInfo();

get_header();
?>
    <div id='toristy-main' class="home">
        <section class="toristy-home-hero" style='<?php echo $imgStyle; ?>'>
            <div class="toristy-search row row-large row-divided">
                <div class="toristy-search-bar">
                    <h1><?php echo $home->GetTitle(); ?></h1>
                    <?php echo $home->GetForm();?>
                </div>
            </div>
        </section>
<!--        <section class="toristy-home-all toristy-home-randoms row row-large row-divided--><?php //echo $isRandom; ?><!--"><div class="toristy-random-items">--><?php //echo implode('', $randoms); ?><!--</div></section>-->
        <section class="toristy-home-info"><div><?php echo (isset($infoOne)) ? $infoOne : ''; ?></div></section>
        <section class="toristy-home-items featured row row-large row-divided<?php echo $isFeature; ?>">
            <div class="toristy-home-items-wrap">
                <h2><?php echo $featured['title']; ?></h2>
                <div class="toristy-items"><?php echo implode('', $featured['services']); ?></div>
            </div>
        </section>
        <section class="toristy-home-info"><div><?php echo (isset($infoTwo)) ? $infoTwo : ''; ?></div></section>
        <section class="toristy-home-items featured row row-large row-divided<?php echo $isRecommend; ?>">
            <div class="toristy-home-items-wrap">
                <h2><?php echo $recommended['title']; ?></h2>
                <div class="toristy-items"><?php echo implode('', $recommended['services']); ?></div>
            </div>
        </section>
        <section class="toristy-home-randoms row row-large row-divided<?php echo $isLocation; ?>">
            <h3>Locations</h3>
            <div class="toristy-random-items"><?php echo implode('', $locations); ?></div>
        </section>
    </div>
<?php

get_footer();