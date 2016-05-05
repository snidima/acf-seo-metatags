<?php


class AcfSeoMetaTags {

    private $_debug = false;

    private $_options;

    private $_metaTags;

    private $_socialMetaTags;

    private $_selectItem;

    static private $_MetaOn = false;

    static private $_SocialOn = false;


    private function setOptions()
    {
        if ( !function_exists('get_field') )
            throw new Exception('Для работы необходим плагин ACF');

        $this->_options = array(
            'SrcData' => get_field('custom_metatags', 'options'),
            'seoPluginClassName' => 'All_in_One_SEO_Pack_Module'
        );

        /*Без этого может произойти полнейший фатал*/
        if ( gettype( $this->_options['SrcData'] ) !== 'array')
            throw new Exception('Добавьте страницы в меню->Метатеги, или отключите плагин');
    }

    private function getCurUrl()
    {
        return $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
    }

    private function noPregInSrcData()
    {
        $this->setMetaTags( array(
            'title'       => $this->_options['SrcData'][$this->_selectItem]['title'],
            'description' => $this->_options['SrcData'][$this->_selectItem]['descriptions']
        ) );
    }

    private function PregInSrcData ( $param )
    {

      $title       = str_replace('%page%', $param, $this->_options['SrcData'][$this->_selectItem]['pagination']);
      $description = str_replace('%page%', $param, $this->_options['SrcData'][$this->_selectItem]['pagination_descriptions']);
        
        $this->setMetaTags( array(
            'title'       => $title,
            'description' => $description
        ) );
    }

    private function setMetaTags( $metaTags )
    {

        foreach ( $metaTags as $key => $item)
            $arr[$key] = $item;

        $arr['keywords'] = $this->_options['SrcData'][$this->_selectItem]['keywords'];

        $this->_metaTags = $arr;
        $this->selectInsertTagsMethod();

    }

    private function selectInsertTagsMethod()
    {
        self::$_MetaOn = $this->_metaTags['description'];

        if ( class_exists( $this->_options['seoPluginClassName'] ) )
            $this->saveMetaTagsWithSeoPlugin();
        else
            $this->saveMetaTagsWithoutSeoPlugin();

        if ( $this->_options['SrcData'][$this->_selectItem]['tags_for_social'] * 1 === 1 ){
            self::$_SocialOn = true;
            $this->saveSocialMetaTags();
          }
    }

    static public function getMetaDescription() {
      return self::$_MetaOn;
    }
    static public function getMetaMicroTegs() {
      return self::$_SocialOn;
    }


    private function noInSrcData()
    {
        throw new Exception('Нет совпадения с url');
    }

    private function findAndSave()
    {
        $result = false;

        foreach ( $this->_options['SrcData'] as $key => $value ) {

            //Если строка не регулярное выражение
            if ( $value['reg'] * 1 !== 1 )
                if ( $_SERVER["SERVER_NAME"].'/'.$value['path'] === $this->getCurUrl() ) {
                    $this->_selectItem = $key;
                    $this->noPregInSrcData();
                    $result = true;
                }

            //Если строка регулярное выражение
            if ( $value['reg'] * 1 === 1 ){

                $str = $_SERVER["SERVER_NAME"].'\/'.$value['path-preg'];

                if ( preg_match( "/$str/i", $this->getCurUrl(), $matches ) ){
                    $this->_selectItem = $key;
                    $this->PregInSrcData( $matches[1] );
                    $result = true;
                }
            }
        }

        if ( !$result ) $this->noInSrcData();
    }

    private function saveMetaTagsWithSeoPlugin()
    {
        $metaTags = $this->_metaTags;

        add_filter('aioseop_title', function() use ( $metaTags ) {
            return $metaTags['title'];
        },11);

        add_filter('aioseop_description', function() use ( $metaTags ) {
            return $metaTags['description'];
        },11);

        add_filter('aioseop_keywords', function() use ( $metaTags ) {
            return $metaTags['keywords'];
        },11);
    }

    private function saveMetaTagsWithoutSeoPlugin()
    {
        $metaTags = $this->_metaTags;

        add_filter( 'pre_get_document_title', function() use ( $metaTags ){
            return $metaTags['title'];
        },11);

        add_action( 'wp_head', function() use ( $metaTags ) {
            echo "<meta name='description' itemprop='description' content='".$metaTags["description"]."' />";
            echo "<meta name='keywords' itemprop='keywords' content='".$metaTags['keywords']."' />";
        },11);
    }

    private function saveSocialMetaTags()
    {
        $socialMetaTags = '';
        foreach ($this->_options['SrcData'][$this->_selectItem]['social'] as $val) {
            $socialMetaTags .= $this->_socialMetaTags = $this->_options['SrcData'][$this->_selectItem]["social_tags_{$val}"];
        }


        add_action( 'wp_head', function() use ( $socialMetaTags ) {
            echo $socialMetaTags;
        },11);
    }

    public function __construct( $param )
    {
        if (function_exists('acf_add_options_page')) {
            acf_add_options_page(array(
                'menu_title' => 'Метатеги',
                'menu_slug'  => 'ACF-SEO-Metatags',
                'capability' => 'edit_posts',
                'redirect'   => false,
                'icon_url'   => 'dashicons-tag'
            ));
        }

        $this->_debug = $param['debug'];
        $this->start();
    }

    private function start()
    {
        try {

            $this->setOptions();
            $this->findAndSave();

        } catch (Exception $e) {

            if ( $this->_debug )
                add_action( 'wp_footer', function() use ( $e ) {
                    echo "<script>";
                    echo "console.group('ACF-SEO-Metatags: {$e->getMessage()}');";
                    echo "console.warn(\"Файл: '{$e->getFile()}\");";
                    echo "console.warn(\"Строке: '{$e->getLine()}\");";
                    echo "console.groupEnd();";
                    echo "</script>";
                });

        }
    }

    private function console_log( $title, $message ){
      if ( !$this->_debug ) return;
      add_action( 'wp_footer', function() use ( $title, $message ) {
        echo "<script>";
        echo "console.group('ACF-SEO-Metatags:');";
        echo "console.warn('$title: $message');";
        echo "console.groupEnd();";
        echo "</script>";
      });
    }

}


?>