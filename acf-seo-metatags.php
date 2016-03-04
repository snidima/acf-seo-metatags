<?php
/*
Plugin Name: ACF-SEO-Metatags
Description: Выставление метатегов( title, description, keywords ) через ACF - Метатеги
Version: 2.0
Author: Snigur Dmitry
*/


require_once( dirname( __FILE__ ) . '/AcfSeoMetatags.php' );

add_action( 'init', function(){
    new AcfSeoMetaTags( array(
        'debug' => true
    ) );
});










?>