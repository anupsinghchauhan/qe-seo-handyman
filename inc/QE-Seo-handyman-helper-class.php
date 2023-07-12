<?php
if (!defined('ABSPATH')) exit;
if (!class_exists('QESEOHANDYMAN_Helper_class')):
    class QESEOHANDYMAN_Helper_class
    {
		/**
         * Validate URL
         * @param  String $url
         * @return ture or false
         */
        public function validate_url($url = '')
        {

            $regex = "((https?|ftp)\:\/\/)?";
            $regex .= "([a-z0-9+!*(),;?&=\$_.-]+(\:[a-z0-9+!*(),;?&=\$_.-]+)?@)?";
            $regex .= "([a-z0-9-.]*)\.([a-z]{2,3})";
            $regex .= "(\:[0-9]{2,5})?";
            $regex .= "(\/([a-z0-9+\$_-]\.?)+)*\/?";
            $regex .= "(\?[a-z+&\$_.-][a-z0-9;:@&%=+\/\$_.-]*)?";
            $regex .= "(#[a-z_.-][a-z0-9+\$_.-]*)?";

            if ($url == "")
            {
                return false;

            }
            else
            {

                if (preg_match("/^$regex$/i", trim($url)))
                {
                    return true;
                }
                else
                {
                    return false;
                }
            }

        }

    }

endif;

