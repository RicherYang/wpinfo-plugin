<?php
defined('RY_WPI_VERSION') or exit('No direct script access allowed');

class RY_WPI_Seo
{
    private static $initiated = false;

    public static function init()
    {
        if (!self::$initiated) {
            self::$initiated = true;

            add_action('wp_head', [__CLASS__, 'basic_og']);
        }
    }

    public static function basic_og()
    {
        $list = [
            'og:type' => 'website',
            'og:title' => esc_attr(get_bloginfo('name')),
            'og:site_name' => esc_attr(get_bloginfo('name')),
            'og:locale' => 'zh_TW'
        ];

        if (is_singular()) {
            if (is_front_page()) {
                $list['og:url'] = esc_url(home_url());
            } else {
                $list['og:type'] = 'article';
                $list['og:title'] = single_post_title('', false);
                $list['og:url'] = esc_url(get_permalink());
                $list['article:published_time'] = get_post_time('c', true);
                $list['article:modified_time'] = get_post_modified_time('c', true);
                $tags = get_the_tags();
                if (is_array($tags)) {
                    $list['article:tag'] = array_column($tags, 'name');
                }
            }
        } else {
            if (is_post_type_archive()) {
                $list['og:title'] = post_type_archive_title('', false);
                $list['og:url'] = get_post_type_archive_link(get_post_type());
            } elseif (is_category() || is_tag()) {
                $list['og:title'] = single_term_title('', false);
                $list['og:url'] = get_term_link(get_queried_object());
            }
        }


        foreach ($list as $property => $content) {
            if (is_array($content)) {
                foreach ($content as $info) {
                    echo '<meta property="' . $property . '" content="' . $info . '" />' . "\n";
                }
            } else {
                echo '<meta property="' . $property . '" content="' . $content . '" />' . "\n";
            }
        }
    }
}

RY_WPI_Seo::init();
