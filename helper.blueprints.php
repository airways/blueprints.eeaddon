<?php

/**
 * ExpressionEngine Blueprints Helper Class
 *
 * @package     ExpressionEngine
 * @subpackage  Helpers
 * @category    Blueprints
 * @author      Brian Litzinger
 * @copyright   Copyright (c) 2010, 2011 - Brian Litzinger
 * @link        http://boldminded.com/add-ons/blueprints
 * @license 
 *
 * Copyright (c) 2011, 2012. BoldMinded, LLC
 * All rights reserved.
 *
 * This source is commercial software. Use of this software requires a
 * site license for each domain it is used on. Use of this software or any
 * of its source code without express written permission in the form of
 * a purchased commercial or other license is prohibited.
 *
 * THIS CODE AND INFORMATION ARE PROVIDED "AS IS" WITHOUT WARRANTY OF ANY
 * KIND, EITHER EXPRESSED OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND/OR FITNESS FOR A
 * PARTICULAR PURPOSE.
 *
 * As part of the license agreement for this software, all modifications
 * to this source must be submitted to the original author for review and
 * possible inclusion in future releases. No compensation will be provided
 * for patches, although where possible we will attribute each contribution
 * in file revision notes. Submitting such modifications constitutes
 * assignment of copyright to the original author (Brian Litzinger and
 * BoldMinded, LLC) for such modifications. If you do not wish to assign
 * copyright to the original author, your license to  use and modify this
 * source is null and void. Use of this software constitutes your agreement
 * to this clause.
 */

class Blueprints_helper
{
    private $site_id;
    private $cache;
    
    public $thumbnail_directory_url;
    public $thumbnail_directory_path;
    
    public function __construct()
    {
        $this->EE =& get_instance();
        $this->site_id = $this->EE->config->item('site_id');
        
        // Create cache
        if (! isset($this->EE->session->cache['blueprints']))
        {
            $this->EE->session->cache['blueprints'] = array();
        }
        $this->cache =& $this->EE->session->cache['blueprints'];
    }
    
    public function get_checkbox_options($k)
    {
        $templates = $this->EE->blueprints_model->get_templates();
        
        $checkbox_options = '';
        $groups = array();
        
        foreach($templates->result_array() as $template)
        {
            if(!in_array($template['group_name'], $groups))
            {
                $checked = ((
                        isset($template['group_name']) AND 
                        isset($this->cache['settings']['channel_show_group']) AND
                        isset($this->cache['settings']['channel_show_group'][$k]) AND 
                        in_array($template['group_name'], $this->cache['settings']['channel_show_group'][$k])
                )) ? TRUE : FALSE;
                
                $checkbox_options .= '<p>';
                $checkbox_options .= form_checkbox(
                                        'channel_show_group['. $k .'][]', 
                                        $template['group_name'], 
                                        $checked, 
                                        'class="show_group" id="channel_show_group['. $k .']['. $template['group_name'] .']"'
                                    );
                
                $checkbox_options .= ' <label for="channel_show_group['. $k .']['. $template['group_name'] .']">Show all <i>'. $template['group_name'] .'</i> templates</label>';
            }
            $groups[] = $template['group_name'];
        }
        
        $checked = (
            isset($this->cache['settings']['channel_show_selected']) AND
            isset($this->cache['settings']['channel_show_selected'][$k]) AND 
            $this->cache['settings']['channel_show_selected'][$k] == 'y'
        ) ? TRUE : FALSE;
        
        $checkbox_options .= '<p>'. form_checkbox(
                                        'channel_show_selected['. $k .']', 
                                        'y',
                                        $checked,
                                        'id="channel_show_selected['. $k .']" class="show_selected"'
                                    );
                                    
        $checkbox_options .= ' <label for="channel_show_selected['. $k .']">Show only specific templates</label></p>';
        
        return $checkbox_options;
    }
    
    public function get_pages()
    {
        // Make sure pages cache is empty, and also see if we are in the CP. Since fieldtype files get loaded
        // on the front end, I don't want unecessary queries/processing to be done when not needed.
        if(!isset($this->cache['pages']) AND REQ == 'CP')
        {
            $this->cache['pages'] = "";
            
            if(array_key_exists('structure', $this->EE->addons->get_installed()))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/structure_pages.php';
                $pages = Structure_Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
            elseif(array_key_exists('pages', $this->EE->addons->get_installed()))
            {
                require_once $this->get_theme_folder_path().'boldminded_themes/libraries/pages.php';
                $pages = Pages::get_instance();
                $this->cache['pages'] = $pages->get_pages($this->EE);
            }
        }

        return $this->cache['pages'];
    }
    
    public function get_theme_folder_path()
    {
        return PATH_THEMES . 'third_party/';
    }
    
    public function get_theme_folder_url()
    {
        return $this->EE->config->slash_item('theme_folder_url') .'third_party/';
    }
    
    public function enable_publish_layout_takeover()
    {
        if(!isset($this->cache['enable_publish_layout_takeover']))
        {
            $this->cache['enable_publish_layout_takeover'] = (isset($this->cache['settings']['enable_publish_layout_takeover']) AND $this->cache['settings']['enable_publish_layout_takeover'] == 'y') ? true : false;
        }
        
        return $this->cache['enable_publish_layout_takeover'];
    }
    
    public function is_publish_form()
    {
        if(REQ != "CP")
        {
            return false;
        }
        
        // if($this->EE->router->class == 'content_publish' AND $this->EE->router->method == 'entry_form')
        // if(
        //     ($this->EE->input->get('C') == 'content_publish' AND $this->EE->input->get('M') == 'entry_form') OR
        //     ($this->EE->input->get('C') == 'javascript' AND $this->EE->input->get('M') == 'load')
        // ){
        if($this->EE->input->get('C') == 'content_publish' AND $this->EE->input->get('M') == 'entry_form')
        {
            return true;
        }
        else
        {
            return false;
        }
    }
    
    /**
      * Retrieve site path
      */
    public function site_path()
    {
        $site_url = $this->EE->config->slash_item('site_path');
        return $site_url ? $site_url : str_replace('themes/', '', PATH_THEMES);
    }
    
    /*
        Allow config overrides
    */
    // public function set_paths()
    // {
    //     // If path and url is set in the user's config file, use them.
    //     if($this->EE->config->item('blueprints.thumbnail_directory_url') AND $this->EE->config->item('blueprints.thumbnail_directory_path'))
    //     {
    //         $this->cache['settings']['thumbnail_directory_url'] = $this->EE->config->item('blueprints.thumbnail_directory_url');
    //         $this->cache['settings']['thumbnail_directory_path'] = $this->EE->config->item('blueprints.thumbnail_directory_path');
    //     }
    //     else
    //     {
    //         $this->cache['thumbnail_directory_url'] = 'images/template_thumbnails/';
    //         
    //         // If the user set the site_path var, use it.
    //         if($this->EE->config->item('site_path'))
    //         {
    //             $this->cache['settings']['thumbnail_directory_path'] = 'images' . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
    //         }
    //         // Or fallback and try to find the site root path.
    //         else
    //         {
    //             // Really? I would think BASEPATH would be the absolute root of the site, not the base of the EE install?
    //             // Is there a variable I don't know about to get the EE webroot path?
    //             $images_path = str_replace('themes', 'images', PATH_THEMES);
    //             $this->cache['settings']['thumbnail_directory_path'] = $images_path . DIRECTORY_SEPARATOR . 'template_thumbnails' . DIRECTORY_SEPARATOR;
    //         }
    //     }
    // }
    
    private function debug($str, $die = false)
    {
        echo '<pre>';
        var_dump($str);
        echo '</pre>';
        
        if($die) die('debug terminated');
    }
    
}