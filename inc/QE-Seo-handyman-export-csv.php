<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('QESEOHANDYMAN_export_CSV')):
    class QESEOHANDYMAN_export_CSV
    {

        /**
         * Download csv file
         * @param  String $filename
         * @return file
         */
        public function download_send_headers($filename)
        {
            // disable caching
            $now = gmdate("D, d M Y H:i:s");
            header("Expires: Tue, 03 Jul 2001 06:00:00 GMT");
            header("Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate");
            header("Last-Modified: {$now} GMT");

            // force download
            header("Content-Type: application/force-download");
            header("Content-Type: application/octet-stream");
            header("Content-Type: application/download");

            // disposition / encoding on response body
            header("Content-Disposition: attachment;filename={$filename}");
            header("Content-Transfer-Encoding: binary");

        }

        /**
         * Download file
         * @return csv file
         */
        public function download_csv_file()
        {

            global $wpdb;

            if (isset($_REQUEST['submit']) && isset($_REQUEST['nonce']))
            {
                $Arrcat = get_terms(array(
                    'hide_empty' => false
                ));
                $post_type = sanitize_text_field($_POST['post_type']);
                $seo_plugin = sanitize_text_field($_POST['seo_plugin']);
                $nonce = $_REQUEST['nonce'];
                if (!wp_verify_nonce($nonce, 'csvgen'))
                {

                    wp_die('Not Valid.. Download nonce..!! ');
                }
                if ($seo_plugin == "youst")
                {

                    $meta = get_option('wpseo_taxonomy_meta');

                    $post_type = ($post_type == "all") ? 'any' : $post_type;

                    $posts = new WP_Query('post_type=' . $post_type . '&posts_per_page=-1&post_status=publish');
                    $posts = $posts->posts;
                    $this->download_send_headers("Qe-seo-handyman-meta-details-" . $post_type . "-" . date("Y-m-d") . ".csv");
                    $output = fopen("php://output", 'w');
                    /**
                     * output the column headings
                     */

                    fputcsv($output, array(
                        'post_id',
                        'url',
                        'post_title',
                        'wpseo_title',
                        'wpseo_metadesc',
                        'type',
                        'term_taxonomy_id'
                    ));
                    if ($post_type == "any" || $post_type == "category")
                    {

                        foreach ($Arrcat as $key => $value)
                        {
                            $modified_values = array(
                                $value->term_id,
                                get_category_link($value->term_id) ,
                                $value->name,
                                (isset($meta[$value->taxonomy][$value->term_id]['wpseo_title'])) ? $meta[$value->taxonomy][$value->term_id]['wpseo_title'] : '',
                                (isset($meta[$value->taxonomy][$value->term_id]['wpseo_desc'])) ? $meta[$value->taxonomy][$value->term_id]['wpseo_desc'] : '',
                                $value->taxonomy,
                                $value->term_taxonomy_id
                            );
                            fputcsv($output, $modified_values);
                        }
                    }

                    if ($post_type != "category")
                    {

                        foreach ($posts as $key => $value)
                        {
                            switch ($value->post_type)
                            {
                                case 'revision':
                                case 'nav_menu_item':
                                break;
                                case 'page':
                                    $permalink = get_page_link($value->ID);
                                break;
                                case 'post':
                                    $permalink = get_permalink($value->ID);
                                break;
                                case 'attachment':
                                    $permalink = get_attachment_link($value->ID);
                                break;
                                default:
                                    $permalink = get_post_permalink($value->ID);
                                break;
                            }
                            // $array[] = '';
                            $modified_values = array(
                                $value->ID,
                                $permalink,
                                $value->post_title,
                                $value->_yoast_wpseo_title,
                                $value->_yoast_wpseo_metadesc,
                                'post'
                            );

                            fputcsv($output, $modified_values);
                        }

                    }
                    fclose($output);
                }

                if ($seo_plugin == "all_in_one")
                {

                    if ($post_type == "all")
                    {

                        $posts = $wpdb->get_results("SELECT aio_post.title,aio_post.description,aio_post.post_id,wp_post.post_type,wp_post.post_title FROM {$wpdb->prefix}aioseo_posts as aio_post LEFT JOIN {$wpdb->prefix}posts as wp_post ON wp_post.ID = aio_post.post_id", ARRAY_A);

                    }
                    else
                    {

                        $posts = $wpdb->get_results("SELECT aio_post.title,aio_post.description,aio_post.post_id,wp_post.post_type,wp_post.post_title FROM {$wpdb->prefix}aioseo_posts as aio_post LEFT JOIN {$wpdb->prefix}posts as wp_post ON wp_post.ID = aio_post.post_id WHERE wp_post.post_type='" . $post_type . "'", ARRAY_A);

                    }

                    $this->download_send_headers("Qe-seo-handyman-meta-details-" . $post_type . "-" . date("Y-m-d") . ".csv");
                    $output = fopen("php://output", 'w');
                    /**
                     * output the column headings
                     */
                    fputcsv($output, array(
                        'post_id',
                        'url',
                        'post_title',
                        'wpseo_title',
                        'wpseo_metadesc',
                        'type'
                    ));

                    foreach ($posts as $key => $value)
                    {

                        switch ($value['post_type'])
                        {
                            case 'revision':
                            case 'nav_menu_item':
                            break;
                            case 'page':
                                $permalink = get_page_link($value['post_id']);
                            break;
                            case 'post':
                                $permalink = get_permalink($value['post_id']);
                            break;
                            case 'attachment':
                                $permalink = get_attachment_link($value['post_id']);
                            break;
                            default:
                                $permalink = get_post_permalink($value['post_id']);
                            break;
                        }
                        // $array[] = '';
                        $modified_values = array(
                            $value['post_id'],
                            $permalink,
                            $value['post_title'],
                            $value['title'],
                            $value['description'],
                            'post'
                        );

                        fputcsv($output, $modified_values);
                    }
                    fclose($output);
                }

                die();
            }
        }
    }

endif;

