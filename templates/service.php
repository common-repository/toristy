<?php
/**
 * package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 * Template Name: Toristy Service Template
 * Template Post Type: toristy-service
 */

use Toristy\Contents\Provider;
use Toristy\Contents\Service;
use Toristy\Cores\Plugin;
use Toristy\Helpers\Domain;

get_header();

global $post;
$page = Plugin::Get('page');
$toristy = $page->Meta($post->ID, $post->post_type, true);
if (!$toristy instanceof Service) {
    Domain::Redirect('');
}
$widget = $page->Widget($toristy);
$location = $toristy->GetStreet();
$address = [];
$count = 0;
$feature = '';
$images = "";
$imgs = $toristy->GetGallery();
$price = $toristy->GetPrice();
if (count($imgs) >= 2) {
    $feature = $imgs[0];
    unset($imgs[0]);
    if (count($imgs) > 1) {
        $images = implode('', $imgs);
    }
}
$title = $toristy->GetName();
$des = $toristy->GetDescription(false);
$maps = $toristy->GetStreetWithMap();
$names = ['Detail', 'Map', 'Reviews'];
$tags = [];
$bols = ['map' => !empty($maps['maps'])];
$texts = $toristy->GetTexts(['attention', 'includes', 'excludes', 'cancellation']);
$titles = array_keys($texts);
$provider = null;
$review = '';
if (property_exists($toristy, 'serviceprovider') && $toristy->serviceprovider instanceof Provider) {
    $review = $toristy->serviceprovider->GetReview();
    $bols['reviews'] = (strlen($review) > 0);
}

foreach ($names as $name) {
    $hash = strtolower($name);
    if (!array_key_exists($hash, $bols) || (isset($bols[$hash]) && $bols[$hash])) {
        $tags[] = "<a class='toristy-service-scroll' href='#toristy-service-$hash'><h4>$name</h4></a>";
    }
}
?>
<div id="toristy-main" class="service row row-large row-divided">
    <div class="toristy-service-nav toristy-fixed">
        <nav><?php echo implode('', $tags); ?></nav>
    </div>
    <div class="toristy-service-wrap">
        <div class="toristy-service-main">
            <header class="toristy-service-title">
                <h1><?php echo $title; ?></h1>
                <?php
                if ($location !== '') {
                    ?>
                    <p><?php echo $location; ?></p>
                    <?php
                }
                ?>
            </header>
            <div class="toristy-service-content">
                <section id="toristy-service-detail" class="toristy-service-scroll-to-item toristy-service-skip">
                    <div class="toristy-detail-image">
                        <?php echo $feature; ?>
                        <?php if ($images !== '') { ?>
                            <div class="toristy-image-thumbs"><?php echo $images;; ?></div>
                            <?php } ?>
                    </div>
                    <!--                    <div class="toristy-detail-provider"><p>This service is provided by </p><h3>--><?php //echo $provider; ?><!--</h3></div>-->
                    <div class="toristy-detail-info">
                        <?php echo $des; ?>
                    </div>
                    <?php
                    foreach ($texts as $title => $text) {
                        $slug = strtolower($title); ?>
                        <div id="toristy-service-<?php echo $title; ?>" class="toristy-block <?php echo $slug; ?>">
                            <div><h3><?php echo ucfirst($title); ?></h3></div>
                            <div><?php echo $text; ?></div>
                        </div>
                        <?php } ?>
                </section>
                <?php
                if ($bols['map']) { ?>
                    <section id="toristy-service-map" class="toristy-service-scroll-to-item">
                        <h3>Map</h3>
                        <p><?php echo $maps['street']; ?></p>
                        <div id="toristy-map" class="toristy-map" data-map="<?php echo htmlspecialchars(json_encode($maps['maps']), ENT_QUOTES, 'UTF-8'); ?>"></div>
                    </section>
                    <?php
                }
                if ($bols['reviews']) { ?>
                    <section id="toristy-service-reviews" class="toristy-service-scroll-to-item">
                        <h3>Reviews</h3>
                        <div class="toristy-cover-reviews"><?php echo $review; ?></div>
                    </section>
                    <?php } ?>

            </div>
        </div>
        <aside class="toristy-service-widget toristy-fixed">
            <?php echo $widget; ?>
            <div class="toristy-price"><?php //echo $price; ?></div>
        </aside>
    </div>
</div>
<?php
get_footer();
exit();
?>