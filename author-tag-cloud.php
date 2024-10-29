<?php
/*
Plugin Name: Author Tag Cloud
Plugin URI: http://www.refactord.com/plugins/author-tag-cloud
Description: Creates a template tag to create an author specific tag cloud.  Also, It creates a new permalink structure to drill down content by author and tag.
Author: Kyle G
Author URI: http://www.refactord.com
Version: 1.05
*/

if(!class_exists('Author_tag_cloud')){
    class Author_tag_cloud {

        function  __construct() {
            $this->add_rules();
        }

        function add_rules(){

            include('includes/refactord-add-rewrite-rules.php');

            $options = array(
                'rules' => array(
                    'tag/([^/]+)/author/([^/]+)/?$' => 'index.php?tag=$matches[1]&author_name=$matches[2]',
                    'tag/([^/]+)/author/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?category_name=$matches[1]&author_name=$matches[2]&paged=$matches[3]',
                    'author/([^/]+)/tag/([^/]+)/?$' => 'index.php?author_name=$matches[1]&tag=$matches[2]',
                    'author/([^/]+)/tag/([^/]+)/page/?([0-9]{1,})/?$' => 'index.php?author_name=$matches[1]&tag=$matches[2]&paged=$matches[3]'
                    )
            );

            $add_rewrite_rules = new Refactord_add_rewrite_rules($options);
        }
    }

    $author_tag_cloud = new Author_tag_cloud;

    /**
    * Display tag cloud.
    *
    *
    */
    function author_tag_cloud( $args = '' ) {
            $defaults = array(
                'author_id' => 0,
                'smallest' => 8,
                'largest' => 22,
                'unit' => 'pt',
                'number' => 45,
                'format' => 'flat',
                'separator' => "\n",
                'orderby' => 'name',
                'order' => 'ASC',
                'exclude' => '',
                'include' => '',
                'link' => 'view',
                'taxonomy' => 'post_tag',
                'echo' => true
            );
            $args = wp_parse_args( $args, $defaults );

            //get the author slug
            $author = get_userdata($args['author_id']);

            if(!$author)
                return;

            $args['author-slug'] = $author->user_nicename;

            //$tags = get_terms( $args['taxonomy'], array_merge( $args, array( 'orderby' => 'count', 'order' => 'DESC' ) ) ); // Always query top tags

            $tags = author_get_terms($args['taxonomy'], $args);

            if ( empty( $tags ) )
                    return;

            foreach ( $tags as $key => $tag ) {
//                    if ( 'edit' == $args['link'] )
//                            $link = get_edit_tag_link( $tag->term_id, $args['taxonomy'] );
//                    else
//                            $link = get_term_link( intval($tag->term_id), $args['taxonomy'] );
                $link = get_bloginfo('url') . "/author/" . $args['author-slug'] . "/tag/" . $tag->slug;
                
                if ( is_wp_error( $link ) )
                        return false;

                $tags[ $key ]->link = $link;
                $tags[ $key ]->id = $tag->term_id;
            }

            $return = generate_author_tag_cloud( $tags, $args ); // Here's where those top tags get sorted according to $args

            $return = apply_filters( 'author_tag_cloud', $return, $args );

            if ( 'array' == $args['format'] || empty($args['echo']) )
                    return $return;

            echo $return;
    }

    if(!function_exists('author_get_terms')){
        function author_get_terms($taxonomy, $r){
            global $wpdb;

            return $wpdb->get_results(
                "SELECT posts.ID, taxonomy.term_id, terms.name,terms.slug, terms.term_group, relationships.term_taxonomy_id,  taxonomy.taxonomy,  taxonomy.description, COUNT(terms.name) AS count

                FROM {$wpdb->prefix}posts AS posts
                JOIN {$wpdb->prefix}term_relationships AS relationships
                JOIN {$wpdb->prefix}term_taxonomy AS taxonomy
                JOIN {$wpdb->prefix}terms AS terms

                WHERE posts.post_author = " . $r['author_id'] . "
                AND posts.post_status = 'publish'
                AND posts.post_type = 'post'
                AND relationships.object_id = posts.ID
                AND relationships.term_taxonomy_id = taxonomy.term_taxonomy_id
                AND taxonomy.taxonomy = '{$taxonomy}'

                AND terms.term_id = taxonomy.term_id
                GROUP BY terms.name
                ORDER BY count DESC"
            );
        }
    }

    function generate_author_tag_cloud( $tags, $args = '' ) {
	global $wp_rewrite;
            $defaults = array(
                'smallest' => 8,
                'largest' => 22,
                'unit' => 'pt',
                'number' => 0,
                'format' => 'flat',
                'separator' => "\n",
                'orderby' => 'name',
                'order' => 'ASC',
                'topic_count_text_callback' => 'default_topic_count_text',
                'topic_count_scale_callback' => 'default_topic_count_scale',
                'filter' => 1,
            );

            if ( !isset( $args['topic_count_text_callback'] ) && isset( $args['single_text'] ) && isset( $args['multiple_text'] ) ) {
                    $body = 'return sprintf (
                            _n(' . var_export($args['single_text'], true) . ', ' . var_export($args['multiple_text'], true) . ', $count),
                            number_format_i18n( $count ));';
                    $args['topic_count_text_callback'] = create_function('$count', $body);
            }

            $args = wp_parse_args( $args, $defaults );
            extract( $args );

            if ( empty( $tags ) )
                    return;

            $tags_sorted = apply_filters( 'tag_cloud_sort', $tags, $args );
            if ( $tags_sorted != $tags  ) { // the tags have been sorted by a plugin
                    $tags = $tags_sorted;
                    unset($tags_sorted);
            } else {
                    if ( 'RAND' == $order ) {
                            shuffle($tags);
                    } else {
                            // SQL cannot save you; this is a second (potentially different) sort on a subset of data.
                            if ( 'name' == $orderby )
                                    uasort( $tags, create_function('$a, $b', 'return strnatcasecmp($a->name, $b->name);') );
                            else
                                    uasort( $tags, create_function('$a, $b', 'return ($a->count > $b->count);') );

                            if ( 'DESC' == $order )
                                    $tags = array_reverse( $tags, true );
                    }
            }

            if ( $number > 0 )
                    $tags = array_slice($tags, 0, $number);

            $counts = array();
            $real_counts = array(); // For the alt tag
            foreach ( (array) $tags as $key => $tag ) {
                    $real_counts[ $key ] = $tag->count;
                    $counts[ $key ] = $topic_count_scale_callback($tag->count);
            }

            $min_count = min( $counts );
            $spread = max( $counts ) - $min_count;
            if ( $spread <= 0 )
                    $spread = 1;
            $font_spread = $largest - $smallest;
            if ( $font_spread < 0 )
                    $font_spread = 1;
            $font_step = $font_spread / $spread;

            $a = array();

            foreach ( $tags as $key => $tag ) {
                    $count = $counts[ $key ];
                    $real_count = $real_counts[ $key ];
                    $tag_link = '#' != $tag->link ? esc_url( $tag->link ) : '#';
                    $tag_id = isset($tags[ $key ]->id) ? $tags[ $key ]->id : $key;
                    $tag_name = $tags[ $key ]->name;
                    $a[] = "<a href='$tag_link' class='tag-link-$tag_id' title='" . esc_attr( $topic_count_text_callback( $real_count ) ) . "' style='font-size: " .
                            ( $smallest + ( ( $count - $min_count ) * $font_step ) )
                            . "$unit;'>$tag_name</a>";
            }

            switch ( $format ) :
            case 'array' :
                    $return =& $a;
                    break;
            case 'list' :
                    $return = "<ul class='wp-tag-cloud'>\n\t<li>";
                    $return .= join( "</li>\n\t<li>", $a );
                    $return .= "</li>\n</ul>\n";
                    break;
            default :
                    $return = join( $separator, $a );
                    break;
            endswitch;

        if ( $filter )
                    return apply_filters( 'wp_generate_tag_cloud', $return, $tags, $args );
        else
                    return $return;
    }
}