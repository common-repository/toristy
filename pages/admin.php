<?php
/**
 * Package: Toristy For WordPress.
 * Author: Toristy<support@toristy.com>.
 */

use Toristy\Cores\Plugin;

$raws = Plugin::Get('admin')->GetTabs();
?>
<style>
    p, select {
        margin-top: 0;
        margin-bottom: 10px !important;
    }
    .toristy-compact {
        width: 100% !important;
    }

</style>
<div id="toristy-admin" class="toristy-admin">
    <h1>Toristy Settings</h1>
    <div class="toristy-nav">
        <ul>
            <?php
                foreach ($raws as $tabs) {
                    list ($name, $key, $title) = $tabs;
                    $c1 = ($key === 'one') ? ' toristy-nav-active' : '';
            ?>
                    <li><a id='toristy-nav-<?php echo $name; ?>' class='toristy-nav-item<?php echo $c1;?>' href='#toristy-tab-<?php echo $name;?>'><?php echo $title; ?></a></li>
            <?php } ?>
        </ul>
    </div>
    <div class="toristy-content toristy-content-hide">
        <?php
            foreach ($raws as $datas) {
                list ($name, $key, $title, $data, $extra, $bol) = $datas;
                $c2 = ($key === 'one') ? ' toristy-tab-active' : '';
                $action = ($bol) ? '' : 'options.php';
        ?>
                <div class="toristy-container">
                    <a class='toristy-nav-toggle' href='#<?php echo $name; ?>'>
                        <?php echo $title; ?>
                    </a>
                    <div id='toristy-tab-<?php echo $name; ?>' class='toristy-tab<?php echo $c2; ?>'>
                        <?php if (!is_null($data)) { ?>
                            <div class='toristy-<?php echo $name; ?>'><?php echo $data; ?></div>
                        <?php } else { ?>
                            <?php //settings_errors("toristy-$key"); ?>
                            <form method='post' action="<?php echo $action; ?>">
                                <?php settings_fields("toristy-$key");?>
                                <?php do_settings_sections("toristy-$key"); ?>
                                <?php if ($bol) {
                                    echo "<p class='submit'>
                                        <span style='pointer-events: none;' class='button button-primary'>Update $name</span>
                                    </p>";
                                } else {
                                    submit_button("Update $name");
                                }
                                     ?>
                            </form>
                            <?php echo $extra; ?>
                        <?php } ?>
                    </div>
                </div>
        <?php } ?>
    </div>
</div>