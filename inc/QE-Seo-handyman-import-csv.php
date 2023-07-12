<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('QESEOHANDYMAN_import_CSV')):
    class QESEOHANDYMAN_import_CSV
    {

        /**
         * Import csv file temporary preview
         * @param  String $seo_plugin
         * @return array
         */
        public function import_csv_file_preview($seo_plugin)
        {

            global $wpdb;
            $arrCat = array();
            $file_extension = wp_check_filetype($_FILES["fileCsv"]["name"]);
            // Validate file input to check if is not empty
            if (!file_exists($_FILES["fileCsv"]["tmp_name"]))
            {
                $notice = 'File input should not be empty.';
                update_option('error_notice_message', $notice, 'no');
            }
            else if ($file_extension['ext'] != "csv")
            {
                $notice = 'Invalid CSV: File must have .csv extension.';
                update_option('error_notice_message', $notice, 'no');
            }
            else
            {

                $file_tmp_name = $_FILES['fileCsv']['tmp_name'];

                if (($handle = fopen($file_tmp_name, "r")) !== false)
                {

                    while (($data = fgetcsv($handle, 1000, ",")) !== false)
                    {
                        array_filter($data);
                        if (is_array($data) && count($data) > 0 && sanitize_text_field($data[0]) != "post_id")
                        {
                            $post_id = sanitize_text_field($data[0]);
                            $wpseo_title = (isset($data[3]) && trim($data[3]) != "") ? sanitize_text_field($data[3]) : '';
                            $wpseo_metadesc = (isset($data[4]) && trim($data[4]) != "") ? sanitize_text_field($data[4]) : '';
                            $arrCat[] = array(
                                'term_id' => sanitize_text_field($data[0]) ,
                                'post_id' => sanitize_text_field($data[0]) ,
                                'taxonomy' => sanitize_text_field($data[5]) ,
                                'wpseo_title' => $wpseo_title,
                                'wpseo_desc' => $wpseo_metadesc,
                                'wpseo_focuskw' => sanitize_text_field($data[2]) ,
                                'permalink' => sanitize_text_field($data[1])
                            );
                        }
                    }

                }
            }

            return $arrCat;

        }
		
        /**
         * Import csv file
         * @param  String $seo_plugin
         * @return true or false
         */
        public function import_csv_file($seo_plugin)
        {
            global $wpdb;
            $file_tmp_name = $_FILES['fileCsv']['tmp_name'];

            $parm = 'success';
            if (count($_POST['wpseo_title']) > 5000)
            {

                $parm = 'error';
                $notice = 'Please check record. Records are should not be more than 5000.';
                update_option('error_notice_message', $notice, 'no');

            }
            else
            {

                $result = $title_flag = $desc_flag = $post_id = 0;
                for ($i = 0;$i < count($_POST['wpseo_title']);$i++)
                {

                    $post_id = (isset($_POST['post_id'][$i])) ? sanitize_text_field($_POST['post_id'][$i]) : 0;
                    $wpseo_title = (isset($_POST['wpseo_title'][$i]) && trim($_POST['wpseo_title'][$i]) != "") ? sanitize_text_field($_POST['wpseo_title'][$i]) : '';
                    $wpseo_metadesc = (isset($_POST['wpseo_desc'][$i]) && trim($_POST['wpseo_desc'][$i]) != "") ? sanitize_text_field($_POST['wpseo_desc'][$i]) : '';
                    $term_id = (isset($_POST['term_id'][$i]) && trim($_POST['term_id'][$i]) != "") ? sanitize_text_field($_POST['term_id'][$i]) : 0;
                    $permalink = (isset($_POST['permalink'][$i]) && trim($_POST['permalink'][$i]) != "") ? sanitize_text_field($_POST['permalink'][$i]) : '';
                    $taxonomy = (isset($_POST['taxonomy'][$i]) && trim($_POST['taxonomy'][$i]) != "") ? sanitize_text_field($_POST['taxonomy'][$i]) : '';
                    $wpseo_focuskw = (isset($_POST['wpseo_focuskw'][$i]) && trim($_POST['wpseo_focuskw'][$i]) != "") ? sanitize_text_field($_POST['wpseo_focuskw'][$i]) : '';

                    if ($seo_plugin == "youst")
                    {

                        $title_flag = $desc_flag = false;

                        $title_flag = update_post_meta($post_id, '_yoast_wpseo_title', $wpseo_title);
                        $desc_flag = update_post_meta($post_id, '_yoast_wpseo_metadesc', $wpseo_metadesc);
                        if ($title_flag || $desc_flag)
                        {
                            $result++;
                        }
                        
                        if ($_POST['taxonomy'][$i] != "post" && $post_id > 0)
                        {
                            $arrCat = array();
                            $arrCat = array(
                                'term_id' => $post_id,
                                'taxonomy' => $taxonomy,
                                'wpseo_title' => $wpseo_title,
                                'wpseo_desc' => $wpseo_metadesc,
                                'wpseo_focuskw' => $wpseo_focuskw,
                                'permalink' => $permalink
                            );
                            $this->save_youst_taxonomy($arrCat);
                        }
                    }

                    if ($seo_plugin == "all_in_one")
                    {

                        $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aioseo_posts where post_id = $post_id", ARRAY_A);

                        if (is_array($results) && count($results) > 0)
                        {

                            $result += $wpdb->update($wpdb->prefix . 'aioseo_posts', array(
                                'title' => $wpseo_title,
                                'description' => $wpseo_metadesc
                            ) , array(
                                'post_id' => $post_id
                            ));
                            //exit( var_dump( $wpdb->last_query ) );
                            
                        }
                        else
                        {

                            $result += $wpdb->insert($wpdb->prefix . 'aioseo_posts', array(
                                'title' => $wpseo_title,
                                'description' => $wpseo_metadesc,
                                'post_id' => $post_id,
                                'created' => gmdate('Y-m-d H:i:s') ,
                                'updated' => gmdate('Y-m-d H:i:s') ,
                            ));
                        }

                    }
                }

                $notice = 'Your metadata has been updated successfully.<br/>Total ' . $result . ' details has been updated.';
                update_option('success_notice_message', $notice, 'no');

            }
            $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=import&responce=' . $parm . '&total_post=' . $result));
            wp_redirect($url);
            exit;

        }

        /**
         * Update broken links in post, pages, etc
         * @return true or false
         */
        public function broken_link_csv_file_process()
        {

            $msg = "Oops..!! something went wrong please try again.";
            if (isset($_POST['broken_link']) && count($_POST['broken_link']) > 0)
            {

                try
                {

                    for ($i = 0;$i < count($_POST['broken_link']);$i++)
                    {
                        $broken_link = (isset($_POST['broken_link'][$i]) && $_POST['broken_link'][$i] != "") ? esc_url_raw(sanitize_text_field($_POST['broken_link'][$i])) : '';
                        $replace_to_link = (isset($_POST['replace_to_link'][$i]) && $_POST['replace_to_link'][$i] != "") ? esc_url_raw(sanitize_text_field($_POST['replace_to_link'][$i])) : '';
                        $ArrReplaceData[$broken_link] = $replace_to_link;
                    }

                    $Arrvalues = $ArrData = array();

                    $result = 0;
                    // GET ALL POST
                    $posts = new WP_Query('post_type=any&posts_per_page=-1&post_status=publish');
                    $posts = $posts->posts;
                    foreach ($posts as $post)
                    {
                        $post_id = $post->ID;
                        $post_content = $post->post_content;
                        foreach ($ArrReplaceData as $broken_link => $replace_to_link)
                        {
                            $post_content = str_replace($broken_link, $replace_to_link, $post_content);
                        }

                        $Arr_post = array(
                            'ID' => $post_id,
                            'post_content' => $post_content,
                        );

                        wp_update_post($Arr_post, true);

                    }

                    $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link&update=success&result=broken_link_updated'));
                    wp_redirect($url);
                    exit;

                }
                catch(exception $e)
                {

                    if (is_array($e->getErrors()) && count($e->getErrors()) > 0)
                    {
                        $msg = $e->getErrors() [0]['message'];
                    }
                    update_option('error_notice_message', $msg, 'no');
                    $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link&responce=error&result=broken_link_updated'));
                    wp_redirect($url);
                    exit;
                }

            }
            else
            {

                update_option('error_notice_message', $msg, 'no');
                $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link&responce=error&result=broken_link_updated'));
                wp_redirect($url);
                exit;
            }

        }
		
		/**
         * Broken link bulk update csv file temporary preview
         * @return array
         */
        public function broken_link_csv_file_preview()
        {

            $ArrResult = $ArrData = array();
            $file_extension = wp_check_filetype($_FILES["fileCsv"]["name"]);
            // Validate file input to check if is not empty
            if (!file_exists($_FILES["fileCsv"]["tmp_name"]))
            {
                $notice = 'File input should not be empty.';
                update_option('error_notice_message', $notice, 'no');
            }
            else if ($file_extension['ext'] != "csv")
            {
                $notice = 'Invalid CSV: File must have .csv extension.';
                update_option('error_notice_message', $notice, 'no');
            }
            else
            {

                $file_tmp_name = $_FILES['fileCsv']['tmp_name'];

                if (($handle = fopen($file_tmp_name, "r")) !== false)
                {
                    $Arrvalues = array();
                    $result = 0;
                    while (($data = fgetcsv($handle, 1000, ",")) !== false)
                    {
                        array_filter($data);
                        $ArrData[] = $data;
                    }
                    $temp = true;
                    foreach ($ArrData as $arrPostData)
                    {
                        if (!$temp)
                        {
                            $broken_link = (isset($arrPostData[0]) && $arrPostData[0] != "") ? esc_url_raw(sanitize_text_field($arrPostData[0])) : '';
                            $replace_to_link = (isset($arrPostData[1]) && $arrPostData[1] != "") ? esc_url_raw(sanitize_text_field($arrPostData[1])) : '';
                            $ArrReplaceData[$broken_link] = (!preg_match("~^(?:f|ht)tps?://~i", $replace_to_link)) ? "http://" . $replace_to_link : $replace_to_link;

                        }
                        $temp = false;

                    }
                    // GET ALL POST
                    $posts = new WP_Query('post_type=any&posts_per_page=-1&post_status=publish');
                    $posts = $posts->posts;
                    foreach ($posts as $post)
                    {
                        $ArrResult = array();
                        $post_id = $post->ID;
                        $post_content = $post->post_content;
                        foreach ($ArrReplaceData as $broken_link => $replace_to_link)
                        {
                            $ArrResult[] = array(
                                'broken_link' => $broken_link,
                                'replace_to_link' => $replace_to_link
                            );
                        }
                    }

                }
            }

            return $ArrResult;

        }
		
		/**
         * Update meta details (such as taxonomy, wpseo_desc, wpseo_title, wpseo_focuskw, etc) in the database table
         * @param wpseo_data
         */
        private function save_youst_taxonomy($wpseo_data = array())
        {

            global $wpdb;
            $meta = get_option('wpseo_taxonomy_meta');
            $term_id = sanitize_text_field($wpseo_data['term_id']);
            $taxonomy = sanitize_text_field($wpseo_data['taxonomy']);
            $wpseo_desc = sanitize_text_field($wpseo_data['wpseo_desc']);
            $wpseo_title = sanitize_text_field($wpseo_data['wpseo_title']);
            $wpseo_focuskw = sanitize_text_field($wpseo_data['wpseo_focuskw']);
            $permalink = sanitize_text_field($wpseo_data['permalink']);
            $arrayCat = array();

            // CREATE NEW ARRAY FOR NEW META DATA TITLE AND DESCRIPTION
            if ($wpseo_desc != "" && $wpseo_title != "")
            {

                $arrayCat[$term_id]['wpseo_desc'] = $wpseo_desc;
                $arrayCat[$term_id]['wpseo_title'] = $wpseo_title;
                $arrayCat[$term_id]['wpseo_focuskw'] = ($wpseo_focuskw == "") ? $wpseo_title : $wpseo_focuskw;

            }
            else
            {

                // REMOVE BLANK META TITAL AND DESCRIPTION DATA
                unset($meta[$taxonomy][$term_id]);
                $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yoast_indexable where object_id = $term_id and object_type='term' and object_sub_type = '" . $taxonomy . "'", ARRAY_A);

                if (is_array($results) && count($results) > 0)
                {

                    $wpdb->delete($wpdb->prefix . 'yoast_indexable', array(
                        'id' => $results[0]['id']
                    ));

                }
            }

            if (count($arrayCat) > 0)
            {

                if (isset($meta[$taxonomy][$term_id]) && is_array($meta[$taxonomy][$term_id]))
                {
                    unset($meta[$taxonomy][$term_id]);
                }

                $r[$taxonomy] = $this->array_merge_recursive($meta[$taxonomy], $arrayCat);
                $c = array_merge($meta, $r);

            }
            else
            {

                $c = $meta;
            }

            update_option('wpseo_taxonomy_meta', $c);
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}yoast_indexable where object_id = $term_id and object_type='term' and object_sub_type = '" . $taxonomy . "'", ARRAY_A);
            //var_dump( $wpdb->last_query );
            if (is_array($results) && count($results) > 0)
            {

                $wpdb->update($wpdb->prefix . 'yoast_indexable', array(
                    'title' => $wpseo_title,
                    'description' => $wpseo_desc
                ) , array(
                    'id' => $results[0]['id']
                ));

            }

        }

		/**
         * Update meta details (such as taxonomy, wpseo_desc, wpseo_title, wpseo_focuskw, etc) in the database table
         * @return array
         */
        public function array_merge_recursive()
        {

            $arrays = func_get_args();
            $base = array_shift($arrays);

            foreach ($arrays as $array)
            {
                @reset($base); //important
                while (list($key, $value) = @each($array))
                {
                    if (is_array($value) && @is_array($base[$key]))
                    {
                        $base[$key] = array_merge_recursive($base[$key], $value);
                    }
                    else
                    {
                        $base[$key] = $value;
                    }
                }
            }

            return $base;
        }
    }

endif;