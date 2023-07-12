<?php
/*
 
Plugin Name: Qe SEO handyman
 
Description: Qe SEO Handyman is a multitool plugin that can greatly help your on-page optimization. You can identify the number of pages with missing/ duplicate meta titles or description and even those with exceeded char limit. You can bulk export or import your meta tags in CSV format. The broken links detected in any 3rd party tools can be replaced simultaneously on all applicable pages using this plugin.
 
Version: 1.0
 
Author: Anup Chauhan

*/

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if (!class_exists('qe_seo_handyman')):

    class qe_seo_handyman {

        public $seo_plugin = "none";
        //plugin class constructor
        public function __construct(){

            // Hook into the admin menu
            $plugin = plugin_basename(__FILE__);

            require_once 'inc/QE-Seo-handyman-export-csv.php';
            require_once 'inc/QE-Seo-handyman-import-csv.php';
            require_once 'inc/QE-Seo-handyman-admin-listpage.php';
            require_once 'inc/QE-Seo-handyman-helper-class.php';

            if (version_compare(PHP_VERSION, '5.3.0') < 0)
            {
                // translators: %s is PHP version
                add_action('admin_notices', array(
                    $this,
                    'qe_admin_version_compare_notice'
                ));
            }

            if (in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins'))))
            {
                $this->seo_plugin = "youst";
            }
            if (in_array('all-in-one-seo-pack/all_in_one_seo_pack.php', apply_filters('active_plugins', get_option('active_plugins'))))
            {
                $this->seo_plugin = "all_in_one";
            }

            if (!in_array('wordpress-seo/wp-seo.php', apply_filters('active_plugins', get_option('active_plugins'))) && !in_array('all-in-one-seo-pack/all_in_one_seo_pack.php', apply_filters('active_plugins', get_option('active_plugins'))))
            {
                add_action('admin_notices', array(
                    $this,
                    'qe_seo_admin_plugin_require_notice'
                ));
            }

            if (isset($_GET['responce']) && $_GET['responce'] == "success")
            {
                add_action('admin_notices', array(
                    $this,
                    'qe_admin_success_notice'
                ));
            }
            if (isset($_GET['responce']) && $_GET['responce'] == "error")
            {
                add_action('admin_notices', array(
                    $this,
                    'qe_admin_error_notice'
                ));
            }

            if (isset($_GET['tab']) && $_GET['tab'] == "broken_link" && isset($_GET['result']) && $_GET['result'] == "broken_link_updated" && isset($_GET['update']) && $_GET['update'] == "success")
            {
                add_action('admin_notices', array(
                    $this,
                    'qe_seo_broken_link_notice'
                ));
            }
            if (isset($_GET['result']) && $_GET['result'] == "meta-updated")
            {
                add_action('admin_notices', array(
                    $this,
                    'qe_seo_dashboard_meta_notice'
                ));
            }

            add_action('admin_menu', array(
                $this,
                'create_plugin_dashboard'
            ));
            add_action('admin_enqueue_scripts', array(
                $this,
                'qe_seo_handyman_admin_script'
            ));
            add_action('admin_enqueue_scripts', array(
                $this,
                'qe_seo_handyman_admin_style'
            ));
            add_action('admin_post_generate_csv', array(
                $this,
                'qe_seo_handyman_posts_csv'
            ));
            add_action('admin_post_import_meta', array(
                $this,
                'import_meta_details_csv_process'
            ));

            add_action('admin_post_update_meta_data', array(
                $this,
                'update_meta_data_process'
            ));
            add_action('admin_post_import_broken_link', array(
                $this,
                'broken_link_csv_process'
            ));
            add_action('admin_init', array(
                $this,
                'admin_init'
            ));
            register_activation_hook(__FILE__, array(
                $this,
                'plugin_options_install'
            ));

        }

        public function admin_init()
        {
            add_action('wp_ajax_save_all_page_meta', array(
                $this,
                'save_all_page_meta'
            ));
        }
        // Register and enqueue a custom stylesheet in the WordPress admin.
        public static function plugin_options_install()
        {

            global $wpdb;
            global $table_name;
            $table_name = $wpdb->prefix . 'qe_seo_handyman_log';

            $charset_collate = '';
            if (!empty($wpdb->charset)) $charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
            if (!empty($wpdb->collate)) $charset_collate .= " COLLATE $wpdb->collate";

            if ($wpdb->get_var("show tables like '" . $table_name . "'") != $table_name)
            {
                $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
				`id` int(10) unsigned NOT NULL AUTO_INCREMENT,
				`broken_link` varchar(100) DEFAULT NULL,
			  	`missing_meta_title` varchar(100) DEFAULT NULL,
			  	`missing_meta_desc` varchar(100) DEFAULT NULL,
			  	`duplicate_meta_title` varchar(100) DEFAULT NULL,
			  	`duplicate_meta_desc` varchar(100) DEFAULT NULL,
			  	`meta_title_char_limit_exce` varchar(100) DEFAULT NULL,
			  	`meta_desc_char_limit_exce` varchar(100) DEFAULT NULL,
			  	`created_date` datetime DEFAULT NULL,
			  	`created_by` int(11) DEFAULT NULL,
			  	PRIMARY KEY (`id`)
			) $charset_collate;";

                $wpdb->query($sql);
                if (!$wpdb->result) throw new Util_Environment_Exception('Can\'t create table ' . $tablename_queue);
            }

        }

        public function qe_seo_handyman_admin_style()
        {
            wp_register_style('qe_seo_css', plugins_url('/css/qe_seo_handyman_style.css', __FILE__) , false, '1.0.0');
            wp_enqueue_style('qe_seo_css');
        }

        // Enqueue a script in the WordPress admin
        public function qe_seo_handyman_admin_script($hook)
        {
            wp_enqueue_script('qe_seo_js', plugins_url('/js/qe_seo_handyman_script.js', __FILE__) , array() , '1.0');
            wp_enqueue_script('qe_seo_flash_msg', plugins_url('/js/qe_flash_msg.js', __FILE__) , array() , '1.0');
        }

        public function create_plugin_dashboard()
        {

            // Add the menu item and page
            $page_title = 'Qe SEO handyman Dashboard';
            $menu_title = 'Qe SEO handyman';
            $capability = 'manage_options';
            $slug = 'qe-seo-handyman';
            $callback = array(
                $this,
                'qe_seo_handyman_plugin_dashboard'
            );
            $icon = plugins_url('/images/favicon.png', __FILE__);
            $position = 100;
            add_menu_page($page_title, $menu_title, $capability, $slug, $callback, $icon, $position);

            add_submenu_page('qe-seo-handyman', 'All pages meta', 'All pages meta', 'manage_options', 'all-pages-meta', array(
                $this,
                'all_pages_meta'
            ));
        }

        public function qe_seo_handyman_plugin_dashboard()
        {

            if (isset($_GET['tab']) && $_GET['tab'] == "export")
            {
                $this->export_meta_details();
            }
            elseif (isset($_GET['tab']) && $_GET['tab'] == "import")
            {
                $this->import_meta_details_csv();
            }
            elseif (isset($_GET['tab']) && $_GET['tab'] == "broken_link")
            {
                $this->broken_link_details();
            }
            else
            {
                $this->dashboard();
            }

        }

        public function all_pages_meta()
        {
            if ($this->seo_plugin == "youst")
            {
                new QESEOYoustListpage();
                return;
            }
            if ($this->seo_plugin == "all_in_one")
            {
                new QESEOAllInOneListpage();
                return;
            }

        }

        // DASHBOARD
        public function dashboard()
        {
            global $wpdb;

            if ($this->seo_plugin == "none")
            {
                $this->plugin_error_dashboard();
            }
            else
            {
                $this->active_dashboard();
            }
        }

        public function plugin_error_dashboard()
        {
?>
		<div class="qe_seo_handyman_view">
    		<h2 class="qe_seo_handyman_page_title"><?php echo esc_html_e('Configuration Error', 'qe-seo-handyman'); ?></h2>
    		<p>
    			<?php printf(__('Qe SEO handyman : You haven’t activated Yoast SEO Plugin or All in One SEO Plugin.<a href="%s" class="qe_seo_link">Install Now</a> here to import post data CSV file. The plugin will update meta details on pages, posts, etc.', 'qe-seo-handyman') , esc_attr(admin_url('plugin-install.php'))); ?>
    		</p>
    	</div>
		<?php
        }

        public function active_dashboard()
        {

            global $wpdb;
            $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}qe_seo_handyman_log order by id desc limit 1", ARRAY_A);
            $refresh_url = add_query_arg(['action' => 'update_meta_data', 'nonce' => wp_create_nonce('updatenonce') , ], admin_url('admin-post.php'));
?>
	    <div class="qe_seo_handyman_view">
	    	<h2 class="qe_seo_handyman_page_title"><?php echo esc_html_e('QeSEO handyman', 'qe-seo-handyman'); ?></h2>
			<p><?php echo esc_html_e('Qe SEO Handyman is a multitool plugin that can greatly help your on-page optimization. You can identify the number of pages with missing/ duplicate meta titles or description and even those with exceeded char limit. You can bulk export or import your meta tags in CSV format. The broken links detected in any 3rd party tools can be replaced simultaneously on all applicable pages using this plugin.', 'qe-seo-handyman'); ?></p>
			<div class="btn-right-center">
			<div class="btn_link">				
				<a href="<?php echo esc_url($refresh_url); ?>" class="button-primary"><img src="<?php echo plugin_dir_url(__FILE__) . 'images/refresh-icon.png'; ?>"> <?php echo esc_html_e('Refresh', 'qe-seo-handyman') ?></a>
				</div>
			</div>
			<div style="clear:both;"></div>
			<div class="main_container">
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['missing_meta_title'])) ? esc_html($results[0]['missing_meta_title']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Missing Meta Title', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['missing_meta_desc'])) ? esc_html($results[0]['missing_meta_desc']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Missing Meta Description', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['duplicate_meta_title'])) ? esc_html($results[0]['duplicate_meta_title']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Duplicate Meta Title', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['duplicate_meta_desc'])) ? esc_html($results[0]['duplicate_meta_desc']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Duplicate Meta Description', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['meta_title_char_limit_exce'])) ? esc_html($results[0]['meta_title_char_limit_exce']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Meta Title Char Limit Exceeded', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
				<div class="broken_link_count one_three">
					<div class="small-box bg-aqua">
				        <div class="inner">
				            <h3> <?php echo (count($results) > 0 && isset($results[0]['meta_desc_char_limit_exce'])) ? esc_html($results[0]['meta_desc_char_limit_exce']) : 0; ?> </h3>
				            <p><?php echo esc_html_e('Meta Description Char Limit Exceeded', 'qe-seo-handyman'); ?></p>
				        </div>
				        <div class="icon">
				           <i class="fa fa-briefcase" aria-hidden="true"></i>
				        </div>
			      	</div>
				</div>
			</div>
			
			<div class="clear_div"></div>
			<div class="container">
				<p><?php echo esc_html_e('Qe SEO handyman plugin is providing meta details and broken link bulk replace features.'); ?></p>
				<div class="row">
				<div class="col-sm-12">
					<!-- Start tabs -->
					<ul class="wp-tab-bar">
						<li class="wp-tab-active tabpanel"><a href="#UpdateMeta"><?php echo esc_html_e('Update Meta Details', 'qe-seo-handyman'); ?></a></li>
						<li class="tabpanel"><a href="#ReplaceBrokenLinks"><?php echo esc_html_e('Replace Broken Links', 'qe-seo-handyman'); ?></a></li>
					</ul>
					<div class="wp-tab-panel" id="UpdateMeta">
						<ul class="qe_seo_handyman_steps">
						<li>
							<?php printf(__('Step 1: <a class="qe_seo_handyman_download_btn" href="%s">Click</a> here to export meta details of posts from the website database.', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=export'))); ?>
						</li>
						<li>
							<?php echo esc_html_e('Step 2: Update necessary post meta details in the exported CSV file.', 'qe-seo-handyman'); ?>
						</li>
						<li>
							<?php printf(__('Step 3: <a class="qe_seo_handyman_download_btn" href="%s">Click</a> here to import post data CSV file. The plugin will update meta details on pages, posts, etc.', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=import'))); ?>
						</li>
						</ul>
					</div>
					<div class="wp-tab-panel" id="ReplaceBrokenLinks" style="display: none;">
						<ul class="qe_seo_handyman_steps">
							<li>
								<?php printf(__('Step 1: <a class="qe_seo_handyman_download_btn" href="%s" download>Click</a> here to download broken links in CSV file format.', 'qe-seo-handyman') , esc_attr(plugins_url('document/broken-link-file.csv', __FILE__))); ?>
							</li>
							<li>
								<?php echo esc_html_e('Step 2: Add broken link and respective replace a link in CSV file.', 'qe-seo-handyman'); ?>
							</li>
							<li>
								<?php printf(__('Step 3: <a class="qe_seo_handyman_download_btn" href="%s">Click</a> here to import broken links CSV file.', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link'))); ?>
							</li>
						</ul>
					</div>
					<!-- End tabs -->
				</div>
				
				</div>
			</div>
	    </div>
	    <?php
        }
        // UPDATE BROKEN LINK IN ALL PAGES
        public function export_meta_details()
        {
        ?>

	    <div class="qe_seo_handyman_view">
	    	<h2 class="qe_seo_handyman_page_title"><?php esc_html_e('Export Meta Details', 'qe-seo-handyman'); ?></h2>
	    	<span class="qe_seo_handyman_page_hints"><?php esc_html_e('Select a option or "All" from the drop-down and export all meta titles and descriptions in CSV file format.', 'qe-seo-handyman'); ?></span>

	    	<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>

	    	<br>
			<br>
			<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>" method="post" enctype='multipart/form-data' >
				<input type="hidden" name="action" value="generate_csv" />
				<?php
            $args = array(
                'public' => true,
                '_builtin' => false
            );
            $output = 'names'; // 'names' or 'objects' (default: 'names')
            $operator = 'and'; // 'and' or 'or' (default: 'and')
            $ArrPost_types = get_post_types($args, $output, $operator);
            ?>
				<select name="post_type">
					<option value="all"><?php esc_html_e('All', 'qe-seo-handyman'); ?></option>
					<?php if ($this->seo_plugin == "youst") { ?>
					<option value="category"><?php esc_html_e('All Taxonomy', 'qe-seo-handyman'); ?></option>
					<?php } ?>
					<option value="post"><?php esc_html_e('Post', 'qe-seo-handyman'); ?></option>
					<option value="page"><?php esc_html_e('Page', 'qe-seo-handyman'); ?></option>
					<option value="attachment"><?php esc_html_e('Attachment', 'qe-seo-handyman'); ?></option>
					<option value="revision"><?php esc_html_e('Revision', 'qe-seo-handyman'); ?></option>
					<option value="nav_menu_item"><?php esc_html_e('Nav Menu Item', 'qe-seo-handyman'); ?></option>
					<?php if (is_array($ArrPost_types) && count($ArrPost_types) > 0): ?>
    					<?php foreach ($ArrPost_types as $key => $post_type): ?>
    					<option value="<?php echo $key; ?>"><?php echo esc_html(ucfirst($post_type)); ?></option>
    					<?php endforeach; ?>
					<?php endif; ?>
				</select>
				<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('csvgen'); ?>">
				<input type="hidden" name="seo_plugin" value="<?php echo $this->seo_plugin; ?>">
				<br/>
				<br/>
				<input type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit">
			</form>
	    </div>
	    <?php
        }
        //Upload CVF file: Meta Title and Description
        public function import_meta_details_csv()
        {
            if (isset($_POST) && !empty($_POST))
            {

                $csv = new QESEOHANDYMAN_import_CSV();
                $result = $csv->import_csv_file_preview($this->seo_plugin);
                $notice = get_option('error_notice_message', false);
                if ($notice)
                {
                    delete_option('error_notice_message');
                    $this->display_dynamic_error_notice($notice);
                }
                if (count($result) > 0)
                { ?>

				<div class="qe_broken_link_table">
					<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>" method="post" enctype='multipart/form-data' >
					<input type="hidden" name="action" value="import_meta" />
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('csvimport'); ?>">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th class="manage-column column-primary">
										<?php esc_html_e('Page Url', 'qe-seo-handyman'); ?>
									</th>
								</tr>
							</thead>
							<?php
                    if (count($result) > 0)
                    {
                        foreach ($result as $key => $value)
                        { 
                            ?>
									<tr class="is-expanded">
										<td class="column-primary">
										<?php
                            if ($value['wpseo_title'] == "" || $value['wpseo_desc'] == "")
                            {
                                echo "<span style='color:red'>" . esc_html($value['permalink']) . "</span>";
                            }
                            elseif (trim($value['wpseo_title']) != "" && strlen(trim($value['wpseo_title'])) > 60)
                            {
                                echo "<span style='color:red'>" . esc_html($value['permalink']) . "</span>";
                            }
                            elseif (trim($value['wpseo_desc']) != "" && strlen(trim($value['wpseo_desc'])) > 160)
                            {
                                echo "<br/><span style='color:red'>" . esc_html($value['permalink']) . "</span>";
                            }
                            else
                            {
                                echo "<br/><span style='color:green'>" . esc_html($value['permalink']) . "</span>";
                            }
                            if (trim($value['wpseo_title']) != "" && strlen(trim($value['wpseo_title'])) > 60)
                            {

                                echo "<br/>";
                                printf(__('<textarea name="wpseo_title[]" style="width:100%">%s</textarea>', 'qe-seo-handyman') , $value['wpseo_title']);
                                echo "<br/><span style='color:red'>" . esc_html('Title should be less then 60 characters.') . "</span>";

                            }
                            else
                            {

                                if ($value['wpseo_title'] == "")
                                {
                                    echo '<br/><span style="color:red">' . esc_html('Meta Title Missing.') . '</span>';
                                }
                                else
                                {
                                    echo '<br/>' . esc_html($value['wpseo_title']) . '</span>';
                                }
                                printf(__('<input type="hidden" name="wpseo_title[]" value="%s" >', 'qe-seo-handyman') , $value['wpseo_title']);
                            }

                            if (trim($value['wpseo_desc']) != "" && strlen(trim($value['wpseo_desc'])) > 160)
                            {

                                echo "<br/>";
                                printf(__('<textarea name="wpseo_desc[]" style="width:100%">%s</textarea>', 'qe-seo-handyman') , $value['wpseo_desc']);
                                echo "<br/><span style='color:red'>" . esc_html('Description should be less then 160 characters.') . "</span>";

                            }
                            else
                            {

                                if ($value['wpseo_desc'] == "")
                                {
                                    echo '<br/><span style="color:red">' . esc_html('Meta Description Missing.') . '</span>';
                                }
                                else
                                {
                                    echo '<br/><span>' . esc_html($value['wpseo_desc']) . '</span>';
                                    echo '<br/>';
                                }
                                printf(__('<input type="hidden" name="wpseo_desc[]" value="%s" >', 'qe-seo-handyman') , $value['wpseo_desc']);
                            }
?>
										</td>
									</tr>
									<?php
                            printf(__('<input type="hidden" name="term_id[]" value="%s" >', 'qe-seo-handyman') , $value['term_id']);
                            printf(__('<input type="hidden" name="permalink[]" value="%s" >', 'qe-seo-handyman') , $value['permalink']);
                            printf(__('<input type="hidden" name="taxonomy[]" value="%s" >', 'qe-seo-handyman') , $value['taxonomy']);
                            printf(__('<input type="hidden" name="wpseo_focuskw[]" value="%s" >', 'qe-seo-handyman') , $value['wpseo_focuskw']);
                            printf(__('<input type="hidden" name="post_id[]" value="%s" >', 'qe-seo-handyman') , $value['post_id']);

                        }
                    }
?>
							
						</table>

						<button type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit"><?php esc_html_e('Continue', 'qe-seo-handyman'); ?></button> 
						
					</form>
					<br>

					<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
				</div>

			<?php } else { ?>

				 <div class="qe_seo_handyman_view">
				 	<div class="qe-section-eight">
				 		<h2 class="qe_seo_handyman_page_title"><?php esc_html_e('Update Meta Title and Description', 'qe-seo-handyman'); ?></h2>	
				 	</div>
				 	<div class="qe-section-four">
				 		<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
				 	</div>
			    	<div class="clear_div"></div>
					<br>
					<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=import')); ?>" method="post" enctype='multipart/form-data' >
						<label><?php esc_html_e('Upload CSV File:', 'qe-seo-handyman'); ?></label>
						<input type="file" name="fileCsv" id="fileCsv" required />
						
						<br/>
						<span style="font-size: 10px"><?php esc_html_e('Note : Please upload 5000 records at a time to get a better result.', 'qe-seo-handyman'); ?></span>
						<br/>
						<?php printf(__('<a class="qe_seo_handyman_download_btn" href="%s" download>Sample CSV File</a>', 'qe-seo-handyman') , esc_attr(plugin_dir_url(__FILE__) . 'images/sample-xls-file.png')); ?>
						<br/>
						<br/>
						<input type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit">
					</form>
			    </div>

			<?php } ?>
		    <?php } else { ?>
		    <div class="qe_seo_handyman_view">
		    	<div class="qe-section-eight">
			 		<h2 class="qe_seo_handyman_page_title"><?php esc_html_e('Update Meta Title and Description', 'qe-seo-handyman'); ?></h2>	
			 	</div>
			 	<div class="qe-section-four">
			 		<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
			 	</div>
		    	<div class="clear_div"></div>
				<br>
				<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=import')); ?>" method="post" enctype='multipart/form-data' >
					<label><?php esc_html_e('Upload CSV File:', 'qe-seo-handyman'); ?></label>
					<input type="file" name="fileCsv" id="fileCsv" required />
					
					<br/>
					<span style="font-size: 10px"><?php esc_html_e('Note : Please upload 5000 records at a time to get a better result.', 'qe-seo-handyman'); ?></span>
					<br/>
					<?php printf(__('<a class="qe_seo_handyman_download_btn" href="%s" download>Sample CSV File</a>', 'qe-seo-handyman') , esc_attr(plugin_dir_url(__FILE__) . 'images/sample-xls-file.png')); ?>
					<br/>
					<br/>
					<input type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit">
				</form>
		    </div>

	    <?php
            }
        }

        // UPDATE BROKEN LINK IN ALL PAGES
        public function broken_link_details()
        {

            if (isset($_POST) && !empty($_POST))
            {

                $csv = new QESEOHANDYMAN_import_CSV();
                $helper_class = new QESEOHANDYMAN_Helper_class();
                $result = $csv->broken_link_csv_file_preview();
                $notice = get_option('error_notice_message', false);
                if ($notice)
                {
                    delete_option('error_notice_message');
                    $this->display_dynamic_error_notice($notice);
                }
                if (count($result) > 0)
                { ?>
				<div class="qe_broken_link_table">
					<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin-post.php')); ?>" method="post" enctype='multipart/form-data' >
					<input type="hidden" name="action" value="import_broken_link" />
					<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('brokenlinkcsv'); ?>">
						<table class="wp-list-table widefat fixed striped">
							<thead>
								<tr>
									<th class="manage-column column-primary"><?php esc_html_e('No.', 'qe-seo-handyman'); ?></th>
									<th class="manage-column"><?php esc_html_e('Broken Link', 'qe-seo-handyman'); ?></th>
									<th class="manage-column"><?php esc_html_e('Replace To Link', 'qe-seo-handyman'); ?></th>
								</tr>	
							</thead>
							<?php
                    if (count($result) > 0)
                    {
                        $i = 1;
                        foreach ($result as $key => $value)
                        {
?>
								<tbody>
									<tr>
										<td><?php echo $i; ?></td>
										<td><?php echo $value['broken_link']; ?></td>
										<td>
											<?php
                                            if ($helper_class->validate_url($value['replace_to_link']))
                                            {
                                                echo esc_html($value['replace_to_link']);
                                                printf(__('<input type="hidden" name="replace_to_link[]" value="%s" >', 'qe-seo-handyman') , esc_url_raw($value['replace_to_link']));
                                            }
                                            else
                                            {
                                                printf(__('<input type="text" name="replace_to_link[]" value="%s" >', 'qe-seo-handyman') , esc_url_raw($value['replace_to_link']));
                                                echo '<span style="color:red">' . esc_html("Please enter valid URL.") . '</span>';
                                            }
                                            ?>
										</td>
									</tr>
								</tbody>
								<?php
                            printf(__('<input type="hidden" name="broken_link[]" value="%s" >', 'qe-seo-handyman') , $value['broken_link']);
                            $i++;
                        }
                    }
?>
							
						</table>

						<button type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit"><?php esc_html_e('Continue', 'qe-seo-handyman'); ?></button> 
						
					</form>
					<br>
					<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
				</div>

			<?php } else { // end count($result) ?>

				<div class="qe_seo_handyman_view">
			    	<h2 class="qe_seo_handyman_page_title"><?php esc_html_e('Update Broken Links', 'qe-seo-handyman'); ?></h2>
			    	<span class="qe_seo_handyman_page_hints"><?php esc_html_e('Please upload a CSV file with broken/not found URLs and the plugin will replace all respective URLs in the database. You can download a sample CSV file from the download link below.', 'qe-seo-handyman'); ?></span>
			    	<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
					<br>
					<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link')); ?>" method="post" enctype='multipart/form-data' >
						<label><?php esc_html_e('Upload CSV File:', 'qe-seo-handyman'); ?></label>
						<input type="file" name="fileCsv" id="fileCsv" required />
						<br/>
						<br/>
						<?php printf(__('<a class="qe_seo_handyman_download_btn" href="%s" download>Sample CSV File</a>', 'qe-seo-handyman') , esc_attr(plugin_dir_url(__FILE__) . 'document/broken-link-file.csv')); ?>
						<br/>
						<br/>
						<input type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit">
					</form>
			    </div>

			<?php } // end count($result) else ?>

			<?php } else { ?>

			    <div class="qe_seo_handyman_view">
			    	<h2 class="qe_seo_handyman_page_title"><?php esc_html_e('Update Broken Links', 'qe-seo-handyman'); ?></h2>
			    	<span class="qe_seo_handyman_page_hints"><?php esc_html_e('Please upload a CSV file with broken/not found URLs and the plugin will replace all respective URLs in the database. You can download a sample CSV file from the download link below.', 'qe-seo-handyman'); ?></span>

			    	<?php printf(__('<a class="button-primary qe_seo_handyman_back" href="%s">Back</a>', 'qe-seo-handyman') , esc_attr(admin_url('admin.php?page=qe-seo-handyman'))); ?>
					<br>
					<form name="frm" id="frm" action="<?php echo esc_attr(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link')); ?>" method="post" enctype='multipart/form-data' >
						<label><?php esc_html_e('Upload CSV File:', 'qe-seo-handyman'); ?></label>
						<input type="file" name="fileCsv" id="fileCsv" required />
						<br/>
						<br/>
						<?php printf(__('<a class="qe_seo_handyman_download_btn" href="%s" download>Sample CSV File</a>', 'qe-seo-handyman') , esc_attr(plugin_dir_url(__FILE__) . 'document/broken-link-file.csv')); ?>
						<br/>
						<br/>
						<input type="submit" name="submit" value="Submit" class="button-primary qe_seo_handyman_submit">
					</form>
			    </div>
	    <?php
            }
        }

        public function update_meta_data_process()
        {
            global $wpdb;
            if (isset($_REQUEST['nonce']))
            {

                if (wp_verify_nonce($_REQUEST['nonce'], 'updatenonce'))
                {

                    $result = $this->get_meta_details();
                    $wpdb->insert($wpdb->prefix . 'qe_seo_handyman_log', array(
                        'broken_link' => $result['broken_link'],
                        'missing_meta_title' => $result['missing_wpseo_title'],
                        'missing_meta_desc' => $result['missing_wpseo_metadesc'],
                        'duplicate_meta_title' => $result['duplicate_meta_title'],
                        'duplicate_meta_desc' => $result['duplicate_meta_desc'],
                        'meta_title_char_limit_exce' => $result['meta_title_char_limit_exce'],
                        'meta_desc_char_limit_exce' => $result['meta_desc_char_limit_exce'],
                        'created_date' => gmdate('Y-m-d H:i:s') ,
                        'created_by' => get_current_user_id() ,
                    ));
                    $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&result=meta-updated'));
                    wp_redirect($url);
                    exit;
                }
                else
                {
                    wp_die('Invalid nonce..!!');
                }

            }

        }

        private function get_meta_details()
        {
            global $wpdb;

            if ($this->seo_plugin == "youst")
            {

                $posts = new WP_Query('post_type=any&posts_per_page=-1&post_status=publish');
                $posts = $posts->posts;
                $missing_wpseo_metadesc = $missing_wpseo_title = $meta_desc_char_limit_exce = $meta_title_char_limit_exce = $duplicate_meta_desc = $broken_link = 0;
                $Arrtitle = $ArrDesc = array();
                foreach ($posts as $key => $value)
                {

                    if ($value->_yoast_wpseo_title == "")
                    {
                        $missing_wpseo_title += 1;
                    }
                    if ($value->_yoast_wpseo_metadesc == "")
                    {
                        $missing_wpseo_metadesc += 1;
                    }
                    if ($value->_yoast_wpseo_title != "" && strlen($value->_yoast_wpseo_title) > 60)
                    {
                        $meta_title_char_limit_exce += 1;
                    }
                    if ($value->_yoast_wpseo_metadesc != "" && strlen($value->_yoast_wpseo_metadesc) > 160)
                    {
                        $meta_desc_char_limit_exce += 1;
                    }
                    if ($value->_yoast_wpseo_title != "")
                    {
                        $Arrtitle[] = $value->_yoast_wpseo_title;
                    }
                    if ($value->_yoast_wpseo_metadesc != "")
                    {
                        $ArrDesc[] = $value->_yoast_wpseo_metadesc;
                    }

                }
                $duplicate_meta_desc = count($ArrDesc) - count(array_unique($ArrDesc));
                $duplicate_meta_title = count($Arrtitle) - count(array_unique($Arrtitle));

            }

            if ($this->seo_plugin == "all_in_one")
            {

                $results = $wpdb->get_results("SELECT `title`,`description`,`post_id` FROM {$wpdb->prefix}aioseo_posts", ARRAY_A);
                $missing_wpseo_metadesc = $missing_wpseo_title = $meta_desc_char_limit_exce = $meta_title_char_limit_exce = $duplicate_meta_desc = $broken_link = 0;
                $Arrtitle = $ArrDesc = array();
                if (is_array($results) && count($results) > 0)
                {

                    foreach ($results as $key => $value)
                    {

                        if ($value['title'] == "")
                        {
                            $missing_wpseo_title += 1;
                        }
                        if ($value['description'] == "")
                        {
                            $missing_wpseo_metadesc += 1;
                        }
                        if ($value['title'] != "" && strlen($value['title']) > 60)
                        {
                            $meta_title_char_limit_exce += 1;
                        }
                        if ($value['description'] != "" && strlen($value['description']) > 160)
                        {
                            $meta_desc_char_limit_exce += 1;
                        }
                        if ($value['title'] != "")
                        {
                            $Arrtitle[] = $value['title'];
                        }
                        if ($value['description'] != "")
                        {
                            $ArrDesc[] = $value['description'];
                        }

                    }
                }
                $duplicate_meta_desc = count($ArrDesc) - count(array_unique($ArrDesc));
                $duplicate_meta_title = count($Arrtitle) - count(array_unique($Arrtitle));

            }

            return array(
                'broken_link' => $broken_link,
                'missing_wpseo_title' => $missing_wpseo_title,
                'missing_wpseo_metadesc' => $missing_wpseo_metadesc,
                'meta_title_char_limit_exce' => $meta_title_char_limit_exce,
                'meta_desc_char_limit_exce' => $meta_desc_char_limit_exce,
                'duplicate_meta_desc' => $duplicate_meta_desc,
                'duplicate_meta_title' => $duplicate_meta_title,
            );

        }

        public function import_meta_details_csv_process()
        {

            $csv = new QESEOHANDYMAN_import_CSV();

            if (isset($_REQUEST['submit']) && sanitize_text_field($_REQUEST['submit']) == 'Submit' && isset($_REQUEST['nonce']))
            {

                if (!wp_verify_nonce($_REQUEST['nonce'], 'csvimport')) wp_die('Invalid nonce..!!');
                $csv->import_csv_file($this->seo_plugin);
            }
            else
            {
                $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=import'));
                wp_redirect($url);
                exit;
            }

        }

        /**
         * The system will update all broken links in posts
         * CSV file should have two columns CSV file
         *
         */

        public function broken_link_csv_process()
        {

            $csv = new QESEOHANDYMAN_import_CSV();

            if (isset($_REQUEST['submit']) && sanitize_text_field($_REQUEST['submit']) == 'Submit' && isset($_REQUEST['nonce']))
            {

                if (!wp_verify_nonce($_REQUEST['nonce'], 'brokenlinkcsv')) wp_die('Invalid nonce..!!');
                $result = $csv->broken_link_csv_file_process();

            }
            else
            {

                $url = wp_sanitize_redirect(admin_url('admin.php?page=qe-seo-handyman&tab=broken_link'));
                wp_redirect($url);
                exit;
            }
        }

        /**
         * Generate posts details CSV
         * CSV file having Post ID, Post URL, Post Name SEO Title and SEO Description
         *
         */

        public function qe_seo_handyman_posts_csv()
        {

            $csv = new QESEOHANDYMAN_export_CSV();

            if (isset($_REQUEST['submit']) && sanitize_text_field($_REQUEST['submit']) == 'Submit' && isset($_REQUEST['nonce']))
            {

                if (!wp_verify_nonce($_REQUEST['nonce'], 'csvgen')) wp_die('Invalid nonce..!!');
                $csv->download_csv_file();
            }

        }

        // SUCCESS AND ERROR NOTICE FUNCTIONS
        public function qe_admin_success_notice()
        {
            $notice = get_option('success_notice_message', false);
            if ($notice)
            {
                delete_option('success_notice_message');
                $this->display_dynamic_success_notice($notice);
            }
        }
        public function qe_admin_error_notice()
        {

            $notice = get_option('error_notice_message', false);
            if ($notice)
            {
                delete_option('error_notice_message');
                $this->display_dynamic_error_notice($notice);
            }
        }
        public function qe_seo_admin_notice()
        {
            printf(__('<div class="updated"> <p>Your metadata has been updated successfully.<br/>Total %s pages detail has been updated.</p></div>', 'qe-seo-handyman') , esc_html($_GET['total_post']));
        }
        public function display_dynamic_success_notice($notice)
        {
            printf(__('<div class="updated"> <p>%s</p></div>', 'qe-seo-handyman') , sanitize_text_field($notice));
        }
        public function display_dynamic_error_notice($notice)
        {
            printf(__('<div class="notice notice-error"> <p>%s</p></div>', 'qe-seo-handyman') , sanitize_text_field($notice));
        }
        public function qe_admin_version_compare_notice()
        {

            $notice = sprintf(__('Your PHP version is %s, encryption function requires PHP version 5.3.0 or higher.', 'qe-seo-handyman') , PHP_VERSION);
            printf(__('<div class="notice notice-error"> <p>%s</p></div>', 'qe-seo-handyman') , sanitize_text_field($notice));
        }

        public function qe_seo_broken_link_notice()
        {
            echo '<div class="updated"> <p>' . esc_html('All broken links has been replaced successfully.') . '</p></div>';
        }
        public function qe_seo_dashboard_meta_notice()
        {

            echo '<div class="updated"> <p>' . esc_html('All pages meta details has been updated successfully.') . '</p></div>';
        }

        public function qe_seo_admin_plugin_require_notice()
        {
            printf(__('<div class="notice notice-error"> <p>Qe SEO handyman : You haven\'t activated Yoast SEO Plugin or All in One SEO Plugin.<a href="%s" class="qe_seo_link">Install Now</a></p></div>', 'qe-seo-handyman') , esc_attr(admin_url('plugin-install.php')));
        }

        public function save_all_page_meta()
        {

            global $wpdb;

            if (isset($_POST['action']) && sanitize_text_field($_POST['action']) == 'save_all_page_meta' && sanitize_text_field($_POST['post_id']) != '' && sanitize_text_field($_POST['parms']) != '')
            {

                $post_id = sanitize_text_field($_POST['post_id']);

                if ($this->seo_plugin == "youst")
                {
                    if (isset($_POST['parms']) && sanitize_text_field($_POST['parms'] == "title"))
                    {

                        $yoast_wpseo_title = (isset($_POST['seo_title']) && sanitize_text_field($_POST['seo_title']) != "") ? sanitize_text_field($_POST['seo_title']) : '';
                        update_post_meta($post_id, '_yoast_wpseo_title', $yoast_wpseo_title);
                        echo esc_html('Meta Title has been Updated.');
                        exit;
                    }
                    if (isset($_POST['parms']) && $_POST['parms'] == "description")
                    {

                        $yoast_wpseo_metadesc = (isset($_POST['meta_description']) && sanitize_text_field($_POST['meta_description']) != "") ? sanitize_text_field($_POST['meta_description']) : '';
                        update_post_meta($post_id, '_yoast_wpseo_metadesc', $yoast_wpseo_metadesc);
                        echo esc_html('Meta Description has been Updated.');
                        exit;
                    }
                }

                if ($this->seo_plugin == "all_in_one")
                {

                    $results = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}aioseo_posts where post_id = $post_id", ARRAY_A);

                    if (isset($_POST['parms']) && $_POST['parms'] == "title")
                    {

                        $wpseo_title = (isset($_POST['seo_title']) && sanitize_text_field($_POST['seo_title']) != "") ? sanitize_text_field($_POST['seo_title']) : NULL;

                        if (is_array($results) && count($results) > 0)
                        {

                            $wpdb->update($wpdb->prefix . 'aioseo_posts', array(
                                'title' => $wpseo_title
                            ) , array(
                                'post_id' => $post_id
                            ));
                        }
                        else
                        {

                            $wpdb->insert($wpdb->prefix . 'aioseo_posts', array(
                                'title' => $wpseo_title,
                                'post_id' => $post_id,
                                'created' => gmdate('Y-m-d H:i:s') ,
                                'updated' => gmdate('Y-m-d H:i:s') ,
                            ));
                        }
                        echo esc_html('Meta Title has been Updated.');
                        exit;
                    }

                    if (isset($_POST['parms']) && $_POST['parms'] == "description")
                    {

                        $wpseo_metadesc = (isset($_POST['meta_description']) && sanitize_text_field($_POST['meta_description']) != "") ? sanitize_text_field($_POST['meta_description']) : NULL;
                        if (is_array($results) && count($results) > 0)
                        {
                            $wpdb->update($wpdb->prefix . 'aioseo_posts', array(
                                'description' => $wpseo_metadesc
                            ) , array(
                                'post_id' => $post_id
                            ));
                        }
                        else
                        {

                            $wpdb->insert($wpdb->prefix . 'aioseo_posts', array(
                                'description' => $wpseo_metadesc,
                                'post_id' => $post_id,
                                'created' => gmdate('Y-m-d H:i:s') ,
                                'updated' => gmdate('Y-m-d H:i:s') ,
                            ));

                        }
                        echo esc_html('Meta Description has been Updated.');
                        exit;
                    }
                }

            }
            else
            {

                echo esc_html('Oops..!! something went wrong please try again.');
                exit;
            }
            exit;
        }

    }

    new qe_seo_handyman();

    // end class
    
endif;

