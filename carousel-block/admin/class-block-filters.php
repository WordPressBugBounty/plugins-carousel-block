<?php

namespace CarouselSliderBlock\Admin;

if ( ! \defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Block_Filters
 *
 * Controls visibility of legacy blocks in the editor inserter.
 */
class Block_Filters {

    /** @var string[] Legacy block names to hide unless setting is enabled */
    private static $legacy_block_names = [
        'cb/carousel',
    ];

    /**
     * Initialize block filters.
     */
    public static function init() {
        // For blocks registered via PHP (legacy w/out block.json)
        \add_filter( 'register_block_type_args', [ self::class, 'filter_legacy_blocks_inserter_from_php' ], 10, 2 );

        // Belt-and-suspenders fallback for the inserter list
        \add_filter( 'allowed_block_types_all', [ self::class, 'filter_allowed_block_types' ], 10, 2 );
    }

    /**
     * Hide legacy blocks from inserter at registration time for PHP registered blocks.
     *
     * @param array                 $args       Arguments passed to register_block_type().
     * @param \WP_Block_Type|string $block_type \WP_Block_Type object or string name (older).
     * @return array
     */
    public static function filter_legacy_blocks_inserter_from_php( $args, $block_type ) {
        if ( self::should_show_legacy() ) {
            return $args;
        }

        // Extract the block name from the parameter (handles both modern and older WP).
        $name = '';
        if ( is_object( $block_type ) && isset( $block_type->name ) ) {
            $name = $block_type->name;
        } elseif ( is_string( $block_type ) ) {
            $name = $block_type;
        }

        if ( $name && \in_array( $name, self::$legacy_block_names, true ) ) {
            if ( ! isset( $args['supports'] ) || ! \is_array( $args['supports'] ) ) {
                $args['supports'] = [];
            }
            $args['supports']['inserter'] = false;
        }

        return $args;
    }

    /**
     * Final allowlist pass to ensure legacy blocks don't appear in the inserter.
     *
     * @param bool|array $allowed_block_types Current allowlist or boolean.
     * @param \WP_Post   $post                The post being edited.
     * @return bool|array
     */
    public static function filter_allowed_block_types( $allowed_block_types, $post ) {
        if ( self::should_show_legacy() ) {
            return $allowed_block_types;
        }

        // If all blocks allowed, convert to explicit list and remove legacy.
        if ( true === $allowed_block_types ) {
            $registry = \WP_Block_Type_Registry::get_instance();
            $all      = \array_keys( $registry->get_all_registered() );
            $allowed  = \array_values( \array_diff( $all, self::$legacy_block_names ) );
            return $allowed;
        }

        // If already a list, strip legacy names.
        if ( \is_array( $allowed_block_types ) ) {
            $allowed_block_types = \array_values(
                \array_diff( $allowed_block_types, self::$legacy_block_names )
            );
        }

        return $allowed_block_types;
    }

    /**
     * Centralized check for the setting (strict boolean).
     */
    private static function should_show_legacy() : bool {
        return (bool) Settings_Utils::get_setting( 'show_legacy_blocks', false );
    }
}
