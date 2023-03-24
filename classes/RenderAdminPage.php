<?php

namespace FlyntComponentsOverview;

defined('ABSPATH') || exit;

class RenderAdminPage
{
    public static function componentsOverview(): void
    {
        $table = new ListTableComponents();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Components Overview', 'flynt-components-overview') ?></h1>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter components list', 'flynt-components-overview') ?></h2>
            <form id="flynt-components-overview" method="get">
                <input type="hidden" name="page" value="<?php isset($_GET['page']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['page']))) : '' ?>" />
                <?php $table->views() ?>
                <?php $table->search_box(esc_attr__('Search', 'flynt-components-overview'), 'search_id'); ?>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }

    public static function postsWithComponent(string $componentName): void
    {
        $table = new ListTablePosts();
        $table->prepare_items();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e($componentName); ?></h1>
            <?php printf(
                '<a href="%s" class="page-title-action">%s</a>',
                esc_url(admin_url('admin.php?page=' . AdminMenu::MENU_SLUG)),
                esc_html('Back to Components Overview', 'flynt-components-overview'),
            ); ?>
            <hr class="wp-header-end">
            <h2 class="screen-reader-text"><?php esc_html_e('Filter posts list', 'flynt-components-overview') ?></h2>
            <form id="flynt-posts-with-component-overview" method="get">
                <input type="hidden" name="page" value="<?php isset($_GET['page']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['page']))) : '' ?>" />
                <input type="hidden" name="postType" value="<?php isset($_GET['postType']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['postType']))) : '' ?>" />
                <input type="hidden" name="componentName" value="<?php isset($_GET['componentName']) ? esc_html_e(sanitize_text_field(wp_unslash($_GET['componentName']))) : '' ?>" />
                <?php $table->views() ?>
                <?php $table->search_box(esc_attr__('Search', 'flynt-components-overview'), 'search_posts'); ?>
                <?php $table->display() ?>
            </form>
        </div>
        <?php
    }
}
