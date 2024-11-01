<?php
/**
 * Package: Toristy Booking.
 * Author: Toristy<support@toristy.com>.
 */

namespace Toristy\Cores\Admins;


use Toristy\Cores\Page;
use Toristy\Cores\Plugin;
use Toristy\Cores\Settings;
use WP_Post;

class TabServices extends AdminTab
{
    /**
     * @var Page
     */
    private $Page;
    /**
     * @var array
     */
    private $Posts;

    public function __construct(Settings $settings, string $key)
    {
        $this->Title = 'Home Services';
        $this->Page = Plugin::Get('page');
        parent::__construct($settings, 'toristy_home_services', [], 'services', $key);
    }

    public function Load($args)
    {
        $key = $args['key'];
        $name = $args["name"];
        $title = $args['title'];
        $options = (isset($this->Options[$key])) ? $this->Options[$key] : [];
        $value = (isset($options[$name]) && $options[$name] !== '') ? $options[$name] : '';
        if ($title === 'Title') {
            echo '<input class="toristy-input" name="'.$this->Option.'['.$key.']['.$name.']" type="text" value="'.$value.'" />';
            echo '<p style="color: red">limit of 5 words</p>';
        } else {
            $id = (int)$value;
            $select = ["<option value='0'>$title</option>"];
            foreach ($this->Posts() as $post)
            {
                if ($post instanceof WP_Post) {
                    $sName = $post->post_title;
                    if ($post->ID === $id) { $select[] = "<option value='$post->ID' selected='selected'>$sName</option>"; }
                    else { $select[] = "<option value='$post->ID'>$sName</option>"; }
                }
            }
            echo '<select class="toristy-compact" name="'.$this->Option.'['.$key.']['.$name.']">'.implode('', $select).'</select>';
        }
    }

    private function Posts(): array
    {
        if (!isset($this->Posts)) {
            $this->Posts = $this->Page->All(['post_type' => 'toristy-service', 'posts_per_page' => -1]);
        }
        return $this->Posts;
    }

   public function Section(string $title = '')
   {
       echo '<p>Selected services will show up when no duplicates and all are filled. Random services will show up instead.</p>';
   }

    protected function Populate()
    {
        $this->Add('title-one', 'Title', [
            "key" => 'sec-one',
            "name" => "title-one",
            "title" => "Title"
        ], 'Home Service Section One');
        $this->Add('sec-one-1', 'Service 1', [
            "key" => 'sec-one',
            "name" => "sec-one-1",
            "title" => "Service 1"
        ], 'Home Service Section One');
        $this->Add('sec-one-2', 'Service 2', [
            "key" => 'sec-one',
            "name" => "sec-one-2",
            "title" => "Service 2"
        ], 'Home Service Section One');
        $this->Add('sec-one-3', 'Service 3', [
            "key" => 'sec-one',
            "name" => "sec-one-3",
            "title" => "Service 3"
        ], 'Home Service Section One');

        $this->Add('title-two', 'Title', [
            "key" => 'sec-two',
            "name" => "title-two",
            "title" => "Title"
        ], 'Home Service Section Two');
        $this->Add('sec-two-1', 'Service 1', [
            "key" => 'sec-two',
            "name" => "sec-two-1",
            "title" => "Service 1"
        ], 'Home Service Section Two');
        $this->Add('sec-two-2', 'Service 2', [
            "key" => 'sec-two',
            "name" => "sec-two-2",
            "title" => "Service 2"
        ], 'Home Service Section Two');
        $this->Add('sec-two-3', 'Service 3', [
            "key" => 'sec-two',
            "name" => "sec-two-3",
            "title" => "Service 3"
        ], 'Home Service Section Two');
    }
}