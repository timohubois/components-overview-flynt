<?php

/**
 * Run with `wp eval-file tests/rest-api.php` on a disposable local Flynt/ACF installation.
 */

use FlyntComponentsOverview\AdminMenu;
use FlyntComponentsOverview\FlexibleContentLayouts;
use FlyntComponentsOverview\PostsWithLayout;
use FlyntComponentsOverview\RenderAdminPage;
use FlyntComponentsOverview\RestApi;

defined('ABSPATH') || exit;

// phpcs:disable WordPress.Security.NonceVerification -- Tests snapshot globals, not unverified form input.

if (wp_get_environment_type() !== 'local') {
    throw new RuntimeException('Use a disposable local WordPress installation.');
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException(esc_html($message));
    }
    echo 'PASS: ' . esc_html($message) . "\n";
};

$previousUser = get_current_user_id();
$administrators = get_users(['role' => 'administrator', 'number' => 1]);
$assert($administrators !== [], 'An administrator is available.');
wp_set_current_user($administrators[0]->ID);

AdminMenu::enqueueAssets('edit.php');
$assert(
    !wp_style_is('components-overview-flynt', 'enqueued')
    && !has_filter('script_module_data_components-overview-flynt'),
    'Plugin assets and module configuration are not added to unrelated admin screens.'
);
AdminMenu::enqueueAssets('toplevel_page_' . AdminMenu::MENU_SLUG);
$assert(
    wp_style_is('components-overview-flynt', 'enqueued')
    && !wp_script_is('components-overview-flynt', 'registered'),
    'The plugin enqueues its stylesheet without registering a classic admin script.'
);
$moduleConfig = apply_filters('script_module_data_components-overview-flynt', []);
ob_start();
wp_script_modules()->print_script_module_data();
$moduleData = ob_get_clean();
$assert(
    str_contains($moduleData, 'wp-script-module-data-components-overview-flynt')
    && str_contains($moduleData, 'application/json')
    && $moduleConfig['endpoint'] === rest_url(RestApi::NAMESPACE . '/table')
    && wp_verify_nonce($moduleConfig['nonce'], 'wp_rest')
    && $moduleConfig['locale'] === determine_locale()
    && $moduleConfig['language'] === apply_filters('wpml_current_language', null),
    'The enqueued module receives JSON configuration with an authenticated REST nonce and UI context.'
);

register_post_type('cof_async_empty', ['public' => true, 'label' => 'Empty fixture type']);
acf_add_local_field_group([
    'key' => 'group_cof_async_test',
    'name' => 'cofAsyncTestComponents',
    'title' => 'Async test',
    'fields' => [[
        'key' => 'field_cof_async_test',
        'name' => 'cofAsyncTestComponents',
        'label' => 'Components',
        'type' => 'flexible_content',
        'layouts' => [[
            'key' => 'layout_cof_async_test',
            'name' => 'cofAsyncTestLayout',
            'label' => 'Async fixture layout',
            'sub_fields' => [],
        ]],
    ]],
    'location' => [
        [['param' => 'post_type', 'operator' => '==', 'value' => 'page']],
        [['param' => 'post_type', 'operator' => '==', 'value' => 'cof_async_empty']],
    ],
]);

$postIds = [];
$subscriberId = null;
$catalog = FlexibleContentLayouts::getInstance();
try {
    foreach (['publish', 'draft', 'private'] as $status) {
        $postId = wp_insert_post([
            'post_type' => 'page',
            'post_title' => 'Async fixture ' . $status,
            'post_status' => $status,
        ], true);
        if (is_wp_error($postId)) {
            throw new RuntimeException($postId->get_error_message());
        }
        $postIds[] = $postId;
        update_post_meta($postId, 'cofAsyncTestComponents', ['cofAsyncTestLayout']);
    }
    $catalog->deleteTransients();

    $view = [
        'page' => AdminMenu::MENU_SLUG,
        AdminMenu::VIEW_NONCE_NAME => wp_create_nonce(AdminMenu::VIEW_NONCE_ACTION),
        'custom_scope' => '0',
    ];
    require_once ABSPATH . 'wp-admin/includes/class-wp-screen.php';
    require_once ABSPATH . 'wp-admin/includes/screen.php';
    require_once ABSPATH . 'wp-admin/includes/template.php';
    $renderPage = static function (array $params): string {
        $get = $_GET;
        $request = $_REQUEST;
        $screen = $GLOBALS['current_screen'] ?? null;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Scoped server-state snapshot only.
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        $bufferLevel = ob_get_level();
        try {
            $_GET = $_REQUEST = wp_slash($params);
            $_SERVER['REQUEST_URI'] = '/wp-admin/admin.php?' . http_build_query($params);
            $GLOBALS['current_screen'] = WP_Screen::get('toplevel_page_' . AdminMenu::MENU_SLUG);
            ob_start();
            RenderAdminPage::page();
            return ob_get_clean();
        } finally {
            while (ob_get_level() > $bufferLevel) {
                ob_end_clean();
            }
            $_GET = $get;
            $_REQUEST = $request;
            $_SERVER['REQUEST_URI'] = $uri;
            $GLOBALS['current_screen'] = $screen;
        }
    };
    $queryCount = 0;
    $counter = static function (WP_Query $query) use (&$queryCount): void {
        if ($query->get('meta_query')) {
            $queryCount++;
        }
    };
    add_action('pre_get_posts', $counter);
    $shell = $renderPage($view);
    remove_action('pre_get_posts', $counter);
    $assert($queryCount === 0, 'The initial overview performs no layout/post usage queries.');
    $assert(
        get_transient(FlexibleContentLayouts::TRANSIENT_BASE_NAME . '.generation') === false,
        'A cold initial page does not create a cache generation.'
    );
    $assert(
        str_contains($shell, 'components-overview-loading-row')
        && !str_contains($shell, 'components-overview-pagination-placeholder'),
        'Cold loading uses table rows, without fake pagination controls.'
    );
    $assert(
        str_contains($shell, 'data-initial-label') && str_contains($shell, '<table'),
        'Overview loading stays in the table.'
    );
    $assert(
        str_contains($shell, 'components-overview-filter-placeholder') && !str_contains($shell, 'class="count"'),
        'The whole filter bar remains a placeholder until its counts are available.'
    );
    $catalog->deleteTransients();
    $queryCount = 0;
    $dateRendererQueries = 0;
    $dateCells = 0;
    $dateQueryCounter = static function (string $sql) use (&$dateRendererQueries): string {
        if (preg_match('/SELECT\s+COUNT\s*\(\s*1\s*\).*\bpost_author\s*=/is', $sql)) {
            $dateRendererQueries++;
        }
        return $sql;
    };
    $dateMarker = static function (string $time) use (&$dateCells): string {
        $dateCells++;
        return $time . '<span class="cof-date-marker"></span>';
    };
    add_action('pre_get_posts', $counter);
    add_filter('query', $dateQueryCounter);
    add_filter('post_date_column_time', $dateMarker);
    try {
        $detail = $renderPage($view + ['layoutName' => 'cofAsyncTestLayout']);
    } finally {
        remove_action('pre_get_posts', $counter);
        remove_filter('query', $dateQueryCounter);
        remove_filter('post_date_column_time', $dateMarker);
    }
    $assert($queryCount === 1, 'The native detail page queries only its posts, not all layout usage or filter counts.');
    $assert($dateRendererQueries <= 1, 'The native date renderer is not constructed again for each row or cell.');
    $assert(
        $dateCells > 0 && substr_count($detail, 'cof-date-marker') === $dateCells,
        'Every date cell retains the native WordPress date filter.'
    );
    $postsWithLayout = new PostsWithLayout();
    $unrelatedGroups = ['cofAsyncTestComponents' => [['name' => 'anotherLayout']]];
    $assert(
        $postsWithLayout->get('cofAsyncTestLayout', $unrelatedGroups, 'page')->posts === [],
        'An empty ownership-aware meta query cannot return unrelated posts.'
    );
    $assert(
        $postsWithLayout->getCount('cofAsyncTestLayout', $unrelatedGroups, 'page', null) === 0,
        'Post retrieval and counts share the same field-group ownership rules.'
    );
    $assert(
        str_contains($detail, 'Async fixture') && !str_contains($detail, 'data-async="table"'),
        'The matching-post table is present in the initial HTML and never requested with AJAX.'
    );
    $assert(str_contains($detail, 'components-overview-filter-placeholder'), 'Detail filters load separately.');
    $assert(
        str_contains($detail, '<p class="description">')
        && str_contains($detail, 'Find content that uses this component.')
        && str_contains($detail, 'Back to Overview')
        && !str_contains($detail, 'page-title-action'),
        'The detail page uses the same description and secondary-link style as the overview.'
    );
    $assert(
        str_contains($shell, 'type="search"') && str_contains($shell, 'Refresh components'),
        'The initial shell includes usable search and page actions.'
    );
    $dispatch = static function (array $params, string $part = 'table', ?string $locale = null): WP_REST_Response {
        $request = new WP_REST_Request('GET', '/' . RestApi::NAMESPACE . '/table');
        $request->set_param('part', $part);
        if ($locale !== null) {
            $request->set_param('ui_locale', $locale);
        }
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $request->set_param('view', rtrim(strtr(base64_encode($query), '+/', '-_'), '='));
        return rest_do_request($request);
    };
    $previousGet = $_GET;
    $previousRequest = $_REQUEST;
    $previousScreen = $GLOBALS['current_screen'] ?? null;
    $response = $dispatch($view + ['s' => 'Async fixture layout']);
    $html = $response->get_data()['html'] ?? '';
    $assert(
        $response->get_status() === 200 && str_contains($html, 'cofAsyncTestLayout'),
        'REST renders the native overview.'
    );
    $assert(str_contains($html, 'custom_scope=0'), 'Navigation preserves custom view context.');
    $assert(!str_contains($html, '<form') && !str_contains($html, '<h1'), 'REST returns only the overview table.');
    $assert(!str_contains($html, 'class="count"'), 'Filter counts do not block the table response.');
    $queryCount = 0;
    add_action('pre_get_posts', $counter);
    $warmRows = $renderPage($view + ['s' => 'Async fixture layout']);
    remove_action('pre_get_posts', $counter);
    $assert($queryCount === 0, 'Cached rows render without usage/count queries.');
    $assert(
        str_contains($warmRows, 'cofAsyncTestLayout') && !str_contains($warmRows, 'data-async="table"')
        && str_contains($warmRows, 'data-async="views"'),
        'A cached overview renders immediately, while missing filter counts remain asynchronous.'
    );
    $assert($dispatch($view, 'invalid')->get_status() === 400, 'Unknown response parts are rejected.');
    $assert($dispatch($view, 'table', 'not-installed')->get_status() === 400, 'Unknown UI locales are rejected.');
    $counts = $dispatch($view, 'views');
    $assert(
        $counts->get_status() === 200 && str_contains($counts->get_data()['html'] ?? '', 'class="count"'),
        'Filter links and counts are available together.'
    );
    $assert(
        !str_contains($counts->get_data()['html'], 'postType=cof_async_empty'),
        'Overview filters hide post types with zero layouts.'
    );
    $queryCount = 0;
    add_action('pre_get_posts', $counter);
    $warmPage = $renderPage($view + ['s' => 'Async fixture layout']);
    $emptyPage = $renderPage($view + ['postType' => 'cof_async_empty']);
    remove_action('pre_get_posts', $counter);
    $assert($queryCount === 0, 'A fully warm overview renders rows and filter counts without usage queries.');
    $assert(
        str_contains($warmPage, 'cofAsyncTestLayout') && str_contains($warmPage, 'class="count"')
        && !str_contains($warmPage, 'data-async=') && !str_contains($warmPage, '<noscript>'),
        'A fully warm overview needs no REST requests or loading UI.'
    );
    $assert(
        str_contains($emptyPage, 'No items found.') && !str_contains($emptyPage, 'data-async="table"'),
        'A cached empty list is a cache hit, not a loading state.'
    );
    $counts = $dispatch($view + ['layoutName' => 'cofAsyncTestLayout'], 'views');
    $assert(
        !str_contains($counts->get_data()['html'], 'postType=cof_async_empty'),
        'Detail filters hide post types with zero matching posts.'
    );
    $counts = $dispatch($view + ['layoutName' => 'cofAsyncTestLayout', 's' => 'no-fixture-matches-this'], 'views');
    $assert(
        str_contains($counts->get_data()['html'], 'class="count">(0)</span>')
        && !str_contains($counts->get_data()['html'], 'postType='),
        'All remains available when no posts match the search.'
    );
    $assert(
        !str_contains($html, 'rest_route') && !str_contains($html, '/v1/table'),
        'Links target the admin, not REST.'
    );
    $assert($_GET === $previousGet && $_REQUEST === $previousRequest, 'Request globals are restored.');
    $assert(($GLOBALS['current_screen'] ?? null) === $previousScreen, 'The previous screen is restored.');
    $assert(!wp_doing_ajax(), 'The temporary AJAX context is removed.');
    $assert($response->get_headers()['Cache-Control'] === 'no-store', 'Responses are not cacheable.');

    foreach (['publish', 'draft', 'private'] as $status) {
        $html = $renderPage($view + ['layoutName' => 'cofAsyncTestLayout', 's' => 'Async fixture ' . $status]);
        $assert(
            str_contains($html, 'Async fixture ' . $status),
            'Native rendering retains admin visibility for ' . $status . ' posts.'
        );
    }
    $html = $renderPage($view + ['layoutName' => 'cofAsyncTestLayout', 's' => 'no-fixture-matches-this']);
    $assert(str_contains($html, 'No items found.'), 'A search without matches keeps the valid detail screen.');
    $html = $renderPage($view + ['layoutName' => 'cofAsyncTestLayout', 'paged' => '999999']);
    $assert(str_contains($html, 'Async fixture'), 'Native post pagination recovers an out-of-range page.');
    $response = $dispatch($view + ['s' => 'Async fixture layout', 'paged' => '999999']);
    $assert(
        str_contains($response->get_data()['html'] ?? '', 'cofAsyncTestLayout'),
        'Overview pagination is clamped.'
    );
    $assert(
        str_contains($renderPage($view + ['layoutName' => 'not-a-real-layout']), 'Layout not found'),
        'Unknown layouts remain unavailable.'
    );
    $assert(
        $dispatch($view + ['layoutName' => 'cofAsyncTestLayout'])->get_status() === 400,
        'Matching-post tables are not loaded through REST.'
    );

    foreach ([['s' => ['invalid']], ['paged' => '-1'], ['action' => 'refreshLayoutCache']] as $invalid) {
        $assert($dispatch($invalid + $view)->get_status() === 400, 'Malformed/action parameters are rejected.');
    }
    $assert(
        $dispatch([AdminMenu::VIEW_NONCE_NAME => 'invalid'] + $view)->get_status() === 403,
        'Invalid view nonces fail.'
    );
    // Change only this process's field definitions; persisted fixture content stays untouched.
    $fieldGroupsProperty = new ReflectionProperty($catalog, 'fieldGroups');
    $previousGroups = $fieldGroupsProperty->getValue($catalog);
    $groups = $previousGroups;
    $groups['cofAsyncUnused'] = [['name' => 'cofAsyncTestLayout', 'label' => 'Unused duplicate label']];
    try {
        $fieldGroupsProperty->setValue($catalog, $groups);
        $duplicatePage = $renderPage($view + ['layoutName' => 'cofAsyncTestLayout']);
        $assert(
            str_contains($duplicatePage, '<h1 class="wp-heading-inline">Async fixture layout</h1>'),
            'An unused duplicate cannot override the used component label.'
        );
        $groups['cofAsyncTestComponents'] = [];
        $fieldGroupsProperty->setValue($catalog, $groups);
        $assert(
            str_contains($renderPage($view + ['layoutName' => 'cofAsyncTestLayout']), 'Layout not found'),
            'Saved values in a non-owning field group do not make a layout available.'
        );
    } finally {
        $fieldGroupsProperty->setValue($catalog, $previousGroups);
    }
    $unsigned = $view;
    unset($unsigned[AdminMenu::VIEW_NONCE_NAME]);
    $assert($dispatch($unsigned)->get_status() === 403, 'Missing view nonces fail.');

    wp_set_current_user(0);
    $assert($dispatch($view)->get_status() === 401, 'Anonymous users cannot read the table.');
    $subscriberId = wp_insert_user([
        'user_login' => 'cof-async-' . wp_generate_password(10, false, false),
        'user_pass' => wp_generate_password(),
        'role' => 'subscriber',
    ]);
    if (is_wp_error($subscriberId)) {
        throw new RuntimeException($subscriberId->get_error_message());
    }
    wp_set_current_user($subscriberId);
    $assert($dispatch($view)->get_status() === 403, 'Authenticated users without manage_options are denied.');
    wp_set_current_user($administrators[0]->ID);

    $throw = static function (WP_Query $query): void {
        if ($query->get('meta_query')) {
            throw new RuntimeException('Fixture query failure.');
        }
    };
    add_action('pre_get_posts', $throw);
    $caught = false;
    $bufferLevel = ob_get_level();
    try {
        $dispatch($view + ['layoutName' => 'cofAsyncTestLayout'], 'views');
    } catch (RuntimeException $exception) {
        $caught = $exception->getMessage() === 'Fixture query failure.';
    } finally {
        remove_action('pre_get_posts', $throw);
    }
    $assert($caught, 'The failure-path fixture ran.');
    $assert($_GET === $previousGet && $_REQUEST === $previousRequest, 'Failed rendering restores request globals.');
    $assert(
        ($GLOBALS['current_screen'] ?? null) === $previousScreen && !wp_doing_ajax(),
        'Failed rendering restores screen and AJAX context.'
    );
    $assert(ob_get_level() === $bufferLevel, 'Failed rendering closes its output buffers.');
} finally {
    if (is_int($subscriberId)) {
        require_once ABSPATH . 'wp-admin/includes/user.php';
        wp_delete_user($subscriberId);
    }
    foreach ($postIds as $postId) {
        wp_delete_post($postId, true);
    }
    acf_remove_local_field_group('group_cof_async_test');
    unregister_post_type('cof_async_empty');
    $catalog->deleteTransients();
    wp_set_current_user($previousUser);
}
