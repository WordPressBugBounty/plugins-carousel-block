<?php

namespace CarouselSliderBlock\Admin;

use WP_Block_Type_Registry;

class Block_Filters {

    /**
     * Initialize block filters.
     */
    public static function init() {
        add_filter( 'allowed_block_types_all', [ self::class, 'filter_legacy_blocks' ], 10, 2 );
    }

    /**
     * Disallow legacy blocks when setting is enabled.
     *
     * @param array|bool $allowed_block_types
     * @param \WP_Block_Editor_Context $editor_context
     * @return array|bool
     */
    public static function filter_legacy_blocks( $allowed_block_types, $editor_context ) {
        // Bail early if not hiding legacy blocks.		
        if ( ! Settings_Utils::get_setting( 'hide_legacy_carousel', false ) ) {
           return $allowed_block_types;
        }

        // Only apply in post editor.
        if ( isset( $editor_context->name ) && $editor_context->name !== 'core/edit-post' ) {
          return $allowed_block_types;
        }

        // Define blocks to hide.
        $disallowed_blocks = [
            'cb/carousel',
            'cb/slide',
        ];

        // If $allowed_block_types is true or not an array, get all registered blocks.
        if ( ! is_array( $allowed_block_types ) || empty( $allowed_block_types ) ) {
            $registered = WP_Block_Type_Registry::get_instance()->get_all_registered();
            $allowed_block_types = array_keys( $registered );
        }

        // Filter out disallowed blocks.
        $filtered_blocks = [];

        foreach ( $allowed_block_types as $block ) {
            if ( ! in_array( $block, $disallowed_blocks, true ) ) {
                $filtered_blocks[] = $block;
            }
        }

        return $filtered_blocks;
    }
}
