<?php
/*
  Plugin Name: VitalVoice
  Plugin URI: http://vitalvoice.ru
  Description: Озвучивание содержимого сайта лучшим российским синтезом от ЦРТ
  Version: 1.1
  Author: zpearl
  Author URI: http://zeparl.ru
 */
$vvw_voices = array(
    "Vladimir",
    "Alexander",
    "Anna",
    "Maria"
);


add_filter('the_content', 'vvw_add_link');
// create custom plugin settings menu
add_action('admin_menu', 'vvw_create_menu');

add_action('admin_menu', 'create_meta_box');
add_action('save_post', 'save_postdata');

$new_meta_boxes =
        array(
            "vvw_post_speak" => array(
                "name" => "vvw_post_speak",
                "type" => "checkbox",
                "std" => "",
                "title" => "Speak this post",
                "description" => "Check this option to hear this post"),
            "vvw_post_voice" => array(
                "name" => "vvw_post_voice",
                "type" => "select",
                "std" => get_option("vvw_voice"),
                "title" => "Voice to speak",
                "description" => "Select voice",
                "values" => $vvw_voices),
);

function new_meta_boxes() {
    global $post, $new_meta_boxes;

    foreach ($new_meta_boxes as $meta_box) {
        $meta_box_value = get_post_meta($post->ID, $meta_box['name'] . '_value', true);
        if ($meta_box_value == "")
            $meta_box_value = $meta_box['std'];
        echo'<div>
            <input type="hidden" name="' . $meta_box['name'] . '_noncename" id="' . $meta_box['name'] . '_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';
        switch ($meta_box['type']) {
            case 'checkbox':
                if ($meta_box_value)
                    $checked = "checked=\"checked\"";
                echo'<label for="' . $meta_box['name'] . '_value">';
                echo'<input type="checkbox" name="' . $meta_box['name'] . '_value" ' . $checked . ' size="55" />&nbsp;' . $meta_box['description'] . '</label>';
                break;
            case 'select':
                echo'<label for="' . $meta_box['name'] . '_value">' . $meta_box['description'] . '</label>';
                echo '                <select name="' . $meta_box['name'] . '_value">';
                foreach ($meta_box['values'] as $vvw_post_voice) {
                    $selected = "";
                    if ($meta_box_value == $vvw_post_voice)
                        $selected = " selected=\"selected\"";
                    echo '<option' . $selected . '>' . $vvw_post_voice . '</option>';
                }
                echo '                </select>';
                break;
        }
        echo'</div>';
    }
}

function create_meta_box() {
    global $theme_name;
    if (function_exists('add_meta_box')) {
        add_meta_box('new-meta-boxes', 'VitalVoice Post Settings', 'new_meta_boxes', 'post', 'normal', 'high');
        add_meta_box('new-meta-boxes', 'VitalVoice Post Settings', 'new_meta_boxes', 'page', 'normal', 'high');
    }
}

function save_postdata($post_id) {
    global $post, $new_meta_boxes;

    foreach ($new_meta_boxes as $meta_box) {
// Verify
        if (!wp_verify_nonce($_POST[$meta_box['name'] . '_noncename'], plugin_basename(__FILE__))) {
            return $post_id;
        }

        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id))
                return $post_id;
        } else {
            if (!current_user_can('edit_post', $post_id))
                return $post_id;
        }

        $data = $_POST[$meta_box['name'] . '_value'];

        if (get_post_meta($post_id, $meta_box['name'] . '_value') == "")
            add_post_meta($post_id, $meta_box['name'] . '_value', $data, true);
        elseif ($data != get_post_meta($post_id, $meta_box['name'] . '_value', true))
            update_post_meta($post_id, $meta_box['name'] . '_value', $data);
        elseif ($data == "")
            delete_post_meta($post_id, $meta_box['name'] . '_value', get_post_meta($post_id, $meta_box['name'] . '_value', true));
    }
}

function vvw_add_link($content) {
    global $post;
    if ($KeyAPI = get_option('key_API')) {
        if (get_post_meta($post->ID, 'vvw_post_speak_value', true)) {
            $vvw_voice = get_post_meta($post->ID, 'vvw_post_voice_value', true) ? get_post_meta($post->ID, 'vvw_post_voice_value', true) : get_option('vvw_voice');
            require_once 'vitalvoice.inc.php'; //подключаем класс
            $voice = new VitalVoice($KeyAPI);
            $voice->setVoice($vvw_voice);
//        print $voice->getMp3FileURL($tex, "Anna"); //сылка на файл (строка)
            $content .= $voice->getPlayerHTML($content); //html код плеера для вставки на сайт
        }
    }
    return $content;
}

function vvw_create_menu() {

    //create new top-level menu
    add_menu_page('VitalVoice Plugin Settings', 'VitalVoice Settings', 'administrator', __FILE__, 'vvw_settings_page', plugins_url('/images/icon.png', __FILE__));

    //call register settings function
    add_action('admin_init', 'register_mysettings');
}

function register_mysettings() {
    //register our settings
    register_setting('vvw-settings-group', 'key_API');
    register_setting('vvw-settings-group', 'vvw_voice');
}

function vvw_settings_page() {
    global $vvw_voices;
    if (isset($_POST['vvw_updated'])) {
        update_option('key_API', $_POST['key_API']);
        update_option('vvw_voice', $_POST['vvw_voice']);
    }
?>
    <div class="wrap">
        <h2>Vital Voice</h2>

        <form method="post" action="options.php">
<?php settings_fields('vvw-settings-group'); ?>
        <table class="form-table">
            <?php if (!get_option('key_API')) :?>
            <tr valign="top">
                <td colspan="2"><?php echo _e("Please visit <a href=\"http://vitalvoice.ru\">vitalvoice.ru</a> to get Your API Key") ?></td>
            </tr>
            <?php endif; ?>
            <tr valign="top">
                <th scope="row"><?php echo _e("API Key") ?><input name="vvw_updated" type="hidden" value="true" /></th>
                <td><input type="text" name="key_API" value="<?php echo get_option('key_API'); ?>" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php echo _e("Default Voice") ?></th>
                <td>
                    <select name="vvw_voice">
                        <?php
                        $cur_voice = get_option("vvw_voice");
                        foreach ($vvw_voices as $vvw_voice) {
                            $selected = "";
                            if ($cur_voice == $vvw_voice)
                                $selected = " selected=\"selected\"";
                            echo '<option' . $selected . '>' . $vvw_voice . '</option>';
                        }
                        ?>
                    </select>
                </td>
            </tr>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
</div>
<?php } ?>