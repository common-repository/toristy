<?php
namespace Toristy\Cores\Widgets;

use Toristy\Contents\Service;
use Toristy\Cores\Page;
use Toristy\Cores\Plugin;
use Toristy\Cores\Widget;
use WP_Post;

/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

class CalenderWidget extends Widget
{
    /**
     * @var array
     */
    private $Services = [];

    /**
     * @var Page
     */
    private $Page;

    public function __construct(array $options = [], array $controls = [])
    {
        $this->Page = Plugin::Get('page');
        parent::__construct('toristy-calender-widget', 'Toristy Calender', $options, $controls);
    }

    protected function Load(): void
    {
        if (empty($this->Services)) {
            $this->Services = $this->Page->All(['post_type' => 'toristy-service']);
        }
    }

    public function update($new_instance, $old_instance)
    {
        $instance = $old_instance;
        $instance['id'] = sanitize_textarea_field($new_instance['id']);
        $instance['only'] = sanitize_textarea_field($new_instance['only']);
        return $instance;
    }

    public function widget($args, $instance)
    {
        echo $args['before_widget'];
        $data = $this->Get((int)$instance['id']);
        $on = (int)$instance['only'] > 0;
        if (!empty($data) && isset($data['calender']) && isset($data['title'])) {
            $title = $data['title'];
            $calender = $data['calender'];
            echo ($on) ? $args['before_title']. apply_filters('widget_title', $title). $args['after_title'] : '';
            echo $calender;
        }
        echo $args['before_widget'];
    }

    private function Get(int $id): array
    {
        $data = []; $service = null;
        if ($id <= 0) {
            return $data;
        }
        foreach ($this->Services as $ser) {
            if ($ser instanceof WP_Post) {
                if ($ser->ID === $id) {
                    $data['title'] = $ser['name'];
                    $service = $this->Page->Meta($id, 'toristy-service');
                    break;
                }
            }
        }
        if (count($data) > 0 && $service instanceof Service) {
            $calender = $this->Page->Widget($service);
            if ($calender !== '') {
                $data['calender'] = $calender;
            }
            if (!isset($data['calender'])) {
                $data['calender'] = 'No calender found!';
            }
        }
        return $data;
    }

    public function form($instance)
    {
        $id = !empty($instance['id']) ? (int)$instance['id'] : 0;
        $name = esc_attr($this->get_field_id('id'));
        $on = !empty($instance['only']) && (int)$instance['only'] > 0;
        $only = esc_attr($this->get_field_id('only'));
        ?>
        <p>
            <label for="<?php echo $only ?>">With title<label>
                    <select class="widefat" id="<?php echo $only; ?>" name="<?php echo esc_attr($this->get_field_name('only')) ?>">
                        <?php
                        if ($on) {
                            echo "<option value='1' selected='selected'>Yes</option><option value='0'>No</option>";
                        } else {
                            echo "<option value='1'>Yes</option><option selected='selected' value='0'>No</option>";
                        }
                        ?>
                    </select>
        </p>
        <p>
            <label for="<?php echo $name ?>"><label>
                    <select class="widefat" id="<?php echo $name; ?>" name="<?php echo esc_attr($this->get_field_name('id')) ?>">
                        <?php
                        foreach ($this->Services as $ser) {
                            if ($ser instanceof WP_Post) {
                                $key = (int)$ser->ID;
                                $value = $ser->post_title;
                                if ($id > 0 && $id === $key) {
                                    echo "<option value='$key' selected='selected'>$value</option>";
                                } else {
                                    echo "<option value='$key'>$value</option>";
                                }
                            }
                        }
                        ?>
                    </select>
        </p>
        <?php
    }
}