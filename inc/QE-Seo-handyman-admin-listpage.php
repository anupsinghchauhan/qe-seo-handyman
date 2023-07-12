<?php
if (!defined('ABSPATH')) exit;

if (!class_exists('QESEOYoustListpage')):
    class QESEOYoustListpage
    {
        /**
         * Constructor start subpage
         */
        public function __construct()
        {
            $this->list_table_page();

        }
        /**
         * Display the list table page
         *
         * @return Void
         */
        public function list_table_page()
        {
            $ListTable = new QESEOHANDYMAN_YoustPostList();
            $ListTable->prepare_items();

?>
                <div class="wrap">
                    <div id="icon-users" class="icon32"></div>
                    <h2><?php echo esc_html_e('All Pages Meta', 'qe-seo-handyman'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="page" value="wp_list_table_class" />
                        <?php $ListTable->search_box('Search', 'search'); ?>
                        <?php $ListTable->display(); ?>
                    </form>
                </div>
            <?php
        }

    }
endif;

if (!class_exists('QESEOAllInOneListpage')):
    class QESEOAllInOneListpage
    {
        /**
         * Constructor start subpage
         */
        public function __construct()
        {
            $this->list_table_page();

        }
        /**
         * Display the list table page
         *
         * @return Void
         */
        public function list_table_page()
        {
            $ListTable = new QESEOHANDYMAN_AllInOnePostList();
            $ListTable->prepare_items();

?>
                <div class="wrap">
                    <div id="icon-users" class="icon32"></div>
                    <h2><?php echo esc_html_e('All Pages Meta', 'qe-seo-handyman'); ?></h2>
                    <form method="post">
                        <input type="hidden" name="page" value="wp_list_table_class" />
                        <?php $ListTable->search_box('Search', 'search'); ?>
                        <?php $ListTable->display(); ?>
                    </form>
                </div>
            <?php
        }

    }
endif;
// WP_List_Table is not loaded automatically so we need to load it in our application
if (!class_exists('WP_List_Table'))
{
    require_once (ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

/**
 * Create a new table class that will extend the WP_List_Table
 */

if (!class_exists('QESEOHANDYMAN_YoustPostList')):
    class QESEOHANDYMAN_YoustPostList extends WP_List_Table
    {
        /**
         * Prepare the items for the table to process
         *
         * @return Void
         */
        public function prepare_items()
        {
            $columns = $this->get_columns();
            $hidden = $this->get_hidden_columns();
            $sortable = $this->get_sortable_columns();

            $data = $this->table_data();
            usort($data, array(&$this,
                'sort_data'
            ));

            $perPage = 20;
            $currentPage = $this->get_pagenum();
            $totalItems = count($data);

            $this->set_pagination_args(array(
                'total_items' => $totalItems,
                'per_page' => $perPage
            ));

            $data = array_slice($data, (($currentPage - 1) * $perPage) , $perPage);

            $this->_column_headers = array(
                $columns,
                $hidden,
                $sortable
            );
            $this->items = $data;
        }

        /**
         * Override the parent columns method. Defines the columns to use in your listing table
         *
         * @return Array
         */
        public function get_columns()
        {
            $columns = array(
                'ID' => 'ID',
                'post_title' => 'Post Title',
                'preview' => 'Preview',
                'yoast_wpseo_title' => 'Meta Title',
                'yoast_wpseo_metadesc' => 'Meta Description',
            );

            return $columns;
        }

        /**
         * Define which columns are hidden
         *
         * @return Array
         */
        public function get_hidden_columns()
        {
            return array();
        }

        /**
         * Define the sortable columns
         *
         * @return Array
         */
        public function get_sortable_columns()
        {
            return array(
                'ID' => array(
                    'ID',
                    false
                ) ,
                'post_title' => array(
                    'post_title',
                    false
                )
            );
        }

        /**
         * Get the table data
         *
         * @return Array
         */
        private function table_data()
        {
            $search = "";
            if (!empty($_REQUEST['s']))
            {
                $search = esc_sql(sanitize_text_field($_REQUEST['s']));
            }
            $Arrquery = array(
                'post_type' => 'any',
                'posts_per_page' => '-1',
                'post_status' => 'publish',
            );
            if ($search != "") $Arrquery['s'] = $search;

            $posts = new WP_Query($Arrquery);
            $posts = $posts->posts;
            $data = array();
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
                $pre_desc = $con_desc = '';

                if ($value->_yoast_wpseo_title != "")
                {
                    $title = mb_strimwidth($value->_yoast_wpseo_title, 0, 50, '...');
                }
                else
                {
                    $title = mb_strimwidth($value->post_title, 0, 50, '...');
                }
                if ($value->_yoast_wpseo_metadesc != "")
                {
                    $pre_desc = mb_strimwidth($value->_yoast_wpseo_metadesc, 0, 50, '...');
                }
                if (strlen($value->_yoast_wpseo_metadesc) < 50)
                {
                    $alert_cls = 'bad_desc';
                }
                elseif (strlen($value->_yoast_wpseo_metadesc) > 50 && strlen($value->_yoast_wpseo_metadesc) < 160)
                {
                    $alert_cls = 'good_desc';
                }
                elseif (strlen($value->_yoast_wpseo_metadesc) > 160)
                {
                    $alert_cls = 'high_desc';
                }
                else
                {
                    $alert_cls = 'bad_desc';
                }

                if (strlen($value->_yoast_wpseo_title) < 52)
                {
                    $alert_title_cls = 'bad_title';
                }
                elseif (strlen($value->_yoast_wpseo_title) > 52 && strlen($value->_yoast_wpseo_title) < 60)
                {
                    $alert_title_cls = 'good_title';
                }
                elseif (strlen($value->_yoast_wpseo_title) > 60)
                {
                    $alert_title_cls = 'high_title';
                }
                else
                {
                    $alert_title_cls = 'bad_title';
                }

                $con_desc = '<div class="preview_title" id="preview_title_ID_' . $value->ID . '">' . $title . '</div>';
                $con_desc .= '<div class="preview_link" id="preview_link_ID_' . $value->ID . '">' . $permalink . '</div>';
                $con_desc .= '<div class="preview_desc" id="preview_desc_ID_' . $value->ID . '">' . $pre_desc . '</div>';

                $data[] = array(
                    'ID' => $value->ID,
                    'post_title' => '<a href="' . $permalink . '" target="_blank">' . mb_strimwidth($value->post_title, 0, 50, '...') . '</a>',
                    'preview' => '<div style="text-align:left" id="qe_seo_preview_id_' . $value->ID . '">' . $con_desc . '</div>',
                    'yoast_wpseo_title' => '<textarea style="width: 100%" class="qe_seo_title" data-id="' . $value->ID . '">' . $value->_yoast_wpseo_title . '</textarea><span id="qe_seo_title_length_' . $value->ID . '"  class="' . $alert_title_cls . '">' . strlen($value->_yoast_wpseo_title) . '</span>',
                    'yoast_wpseo_metadesc' => '<textarea style="width: 100%" class="qe_seo_metadesc" data-id="' . $value->ID . '">' . $value->_yoast_wpseo_metadesc . '</textarea><span id="qe_seo_desc_length_' . $value->ID . '" class="' . $alert_cls . '">' . strlen($value->_yoast_wpseo_metadesc) . '</span>',
                );

            }

            return $data;
        }

        /**
         * Define what data to show on each column of the table
         *
         * @param  Array $item        Data
         * @param  String $column_name - Current column name
         *
         * @return Mixed
         */
        public function column_default($item, $column_name)
        {
            switch ($column_name)
            {
                case 'ID':
                case 'post_title':
                case 'preview':
                case 'yoast_wpseo_title':
                case 'yoast_wpseo_metadesc':
                    return $item[$column_name];

                default:
                    return print_r($item, true);
            }
        }

        /**
         * Allows you to sort the data by the variables set in the $_GET
         *
         * @return Mixed
         */
        private function sort_data($a, $b)
        {
            // Set defaults
            $orderby = 'ID';
            $order = 'asc';

            // If orderby is set, use this as the sort column
            if (!empty($_GET['orderby']))
            {
                $orderby = $_GET['orderby'];
            }

            // If order is set use this as the order
            if (!empty($_GET['order']))
            {
                $order = $_GET['order'];
            }

            $result = strcmp($a[$orderby], $b[$orderby]);

            if ($order === 'asc')
            {
                return $result;
            }

            return -$result;
        }
    }
endif;

if (!class_exists('QESEOHANDYMAN_AllInOnePostList')):
    class QESEOHANDYMAN_AllInOnePostList extends WP_List_Table
    {
        /**
         * Prepare the items for the table to process
         *
         * @return Void
         */
        public function prepare_items()
        {
            $columns = $this->get_columns();
            $hidden = $this->get_hidden_columns();
            $sortable = $this->get_sortable_columns();

            $data = $this->table_data();
            usort($data, array(&$this,
                'sort_data'
            ));

            $perPage = 20;
            $currentPage = $this->get_pagenum();
            $totalItems = count($data);

            $this->set_pagination_args(array(
                'total_items' => $totalItems,
                'per_page' => $perPage
            ));

            $data = array_slice($data, (($currentPage - 1) * $perPage) , $perPage);

            $this->_column_headers = array(
                $columns,
                $hidden,
                $sortable
            );
            $this->items = $data;
        }

        /**
         * Override the parent columns method. Defines the columns to use in your listing table
         *
         * @return Array
         */
        public function get_columns()
        {
            $columns = array(
                'ID' => 'ID',
                'post_title' => 'Post Title',
                'preview' => 'Preview',
                'wpseo_title' => 'Meta Title',
                'wpseo_metadesc' => 'Meta Description',
            );

            return $columns;
        }

        /**
         * Define which columns are hidden
         *
         * @return Array
         */
        public function get_hidden_columns()
        {
            return array();
        }

        /**
         * Define the sortable columns
         *
         * @return Array
         */
        public function get_sortable_columns()
        {
            return array(
                'post_id' => array(
                    'post_id',
                    false
                ) ,
            );
        }

        /**
         * Get the table data
         *
         * @return Array
         */
        private function table_data()
        {
            global $wpdb;
            $search = "";
            if (!empty($_REQUEST['s']))
            {
                $search = esc_sql(sanitize_text_field($_REQUEST['s']));
            }
            if ($search != "")
            {
                $posts = $wpdb->get_results($wpdb->prepare("SELECT a_post.* FROM {$wpdb->prefix}aioseo_posts as a_post left join {$wpdb->prefix}posts as main_post on main_post.ID = a_post.post_id WHERE ( main_post.post_title LIKE '%s' OR main_post.post_content LIKE '%s') ", '%' . $wpdb->esc_like($search) . '%', '%' . $wpdb->esc_like($search) . '%') , ARRAY_A);
            }
            else
            {
                $posts = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}aioseo_posts") , ARRAY_A);
            }
            //$posts = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}aioseo_posts", ARRAY_A );
            $data = array();

            foreach ($posts as $key => $value)
            {
                $post_id = $value['post_id'];
                if (get_post_type($post_id) != "")
                {
                    switch (get_post_type($post_id))
                    {
                        case 'revision':
                        case 'nav_menu_item':
                        break;
                        case 'page':
                            $permalink = get_page_link($post_id);
                        break;
                        case 'post':
                            $permalink = get_permalink($post_id);
                        break;
                        case 'attachment':
                            $permalink = get_attachment_link($post_id);
                        break;
                        default:
                            $permalink = get_post_permalink($post_id);
                        break;
                    }
                }
                $pre_desc = $con_desc = '';

                if ($value['title'] != "")
                {
                    $title = mb_strimwidth($value['title'], 0, 50, '...');
                }
                else
                {
                    $title = mb_strimwidth(get_the_title($post_id) , 0, 50, '...');
                }
                if ($value['description'] != "")
                {
                    $pre_desc = mb_strimwidth($value['description'], 0, 50, '...');
                }
                if (strlen($value['description']) < 50)
                {
                    $alert_cls = 'bad_desc';
                }
                elseif (strlen($value['description']) > 50 && strlen($value['description']) < 160)
                {
                    $alert_cls = 'good_desc';
                }
                elseif (strlen($value['description']) > 160)
                {
                    $alert_cls = 'high_desc';
                }
                else
                {
                    $alert_cls = 'bad_desc';
                }

                if (strlen($value['title']) < 52)
                {
                    $alert_title_cls = 'bad_title';
                }
                elseif (strlen($value['title']) > 52 && strlen($value['title']) < 60)
                {
                    $alert_title_cls = 'good_title';
                }
                elseif (strlen($value['title']) > 60)
                {
                    $alert_title_cls = 'high_title';
                }
                else
                {
                    $alert_title_cls = 'bad_title';
                }

                $con_desc = '<div class="preview_title" id="preview_title_ID_' . $post_id . '">' . $title . '</div>';
                $con_desc .= '<div class="preview_link" id="preview_link_ID_' . $post_id . '">' . $permalink . '</div>';
                $con_desc .= '<div class="preview_desc" id="preview_desc_ID_' . $post_id . '">' . $pre_desc . '</div>';

                $data[] = array(
                    'ID' => $post_id,
                    'post_title' => '<a href="' . $permalink . '" target="_blank">' . mb_strimwidth(get_the_title($post_id) , 0, 50, '...') . '</a>',
                    'preview' => '<div style="text-align:left" id="qe_seo_preview_id_' . $post_id . '">' . $con_desc . '</div>',
                    'wpseo_title' => '<textarea style="width: 100%" class="qe_seo_title" data-id="' . $post_id . '">' . $value['title'] . '</textarea><span id="qe_seo_title_length_' . $post_id . '"  class="' . $alert_title_cls . '">' . strlen($value['title']) . '</span>',
                    'wpseo_metadesc' => '<textarea style="width: 100%" class="qe_seo_metadesc" data-id="' . $post_id . '">' . $value['description'] . '</textarea><span id="qe_seo_desc_length_' . $post_id . '" class="' . $alert_cls . '">' . strlen($value['description']) . '</span>',
                );

            }

            return $data;
        }

        /**
         * Define what data to show on each column of the table
         *
         * @param  Array $item        Data
         * @param  String $column_name - Current column name
         *
         * @return Mixed
         */
        public function column_default($item, $column_name)
        {
            switch ($column_name)
            {
                case 'ID':
                case 'post_title':
                case 'preview':
                case 'wpseo_title':
                case 'wpseo_metadesc':
                    return $item[$column_name];

                default:
                    return print_r($item, true);
            }
        }

        /**
         * Allows you to sort the data by the variables set in the $_GET
         *
         * @return Mixed
         */
        private function sort_data($a, $b)
        {
            // Set defaults
            $orderby = 'ID';
            $order = 'asc';

            // If orderby is set, use this as the sort column
            if (!empty($_GET['orderby']))
            {
                $orderby = $_GET['orderby'];
            }

            // If order is set use this as the order
            if (!empty($_GET['order']))
            {
                $order = $_GET['order'];
            }

            $result = strcmp($a[$orderby], $b[$orderby]);

            if ($order === 'asc')
            {
                return $result;
            }

            return -$result;
        }
    }
endif;

