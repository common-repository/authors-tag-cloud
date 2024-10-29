<?php
/*
This class will allow you easily add rewrite rules and query vars to your WordPress plugins and themes.  
You can read the tutorial here on how to add rules:  http://www.refactord.com/adding-rewrite-rules-to-wordpress
There you will find out how this class works and how to create your own rules.  

The query var section you are on your own.  There is not yet a tutorial for query vars

*/


if(!class_exists('Refactord_add_rewrite_rules')):
    class Refactord_add_rewrite_rules {

        var $query_vars = array();
        var $rules = array();
        var $show_rules = FALSE; //used for debugging rewrite rules
        var $show_query_vars = FALSE; //used for debugging query vars

        function __construct($options = NULL){
            if(!is_null($options)){
                $this->init($options);
            }
        }

        function init($options){
            foreach($options as $key => $value){
                $this->$key = $value;
            }

            if(!empty($this->rules)){
                add_action('wp_head', array(&$this, 'flush_rules'));
                add_action('generate_rewrite_rules', array(&$this, 'add_rules'));
            }

            if(!empty($this->query_vars)){
                add_filter('query_vars', array(&$this, 'add_query_vars'));
            }

            if($this->show_rules){
                add_action('wp_footer', array(&$this, 'show_rules'), 1);
            }

            if($this->show_query_vars){
                add_action('wp_footer', array(&$this, 'show_query_vars'), 1);
            }
        }

        function add_query_vars($query_vars){
            foreach($this->query_vars as $var){
                $query_vars[] = $var;
            }
            return $query_vars;
        }

        function add_rules(){
            global $wp_rewrite;
            $wp_rewrite->rules = $this->rules + $wp_rewrite->rules;
        }

        function rules_exist(){
            global $wp_rewrite;

            foreach($this->rules as $key => $rule){
                if(!in_array($rule, $wp_rewrite->rules) || !key_exists($key, $wp_rewrite->rules)){
                        return FALSE;
                }
            }
            return TRUE;
        }

        function flush_rules(){
            global $wp_rewrite;
            if(!$this->rules_exist()){
                $wp_rewrite->flush_rules();
            }
        }

        function show_rules(){
            global $wp_rewrite;

            echo "<pre>";
            print_r($wp_rewrite->rules);
            echo "</pre>";
        }

        function show_query_vars(){
            global $wp_query;

            echo "<pre>";
            print_r($wp_query->query_vars);
            echo "</pre>";
        }
    }
endif;