<?php

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Product price/stock importer bundled with the theme.
 *
 * The importer intentionally uses no external plugin. XLSX files are read from
 * their XML payload and legacy XLS files are read from the BIFF workbook stream.
 */

function my_theme_product_import_capability() {
    return class_exists('WooCommerce') ? 'manage_woocommerce' : 'manage_options';
}

function my_theme_product_import_can_manage() {
    return current_user_can(my_theme_product_import_capability()) || current_user_can('manage_options');
}

function my_theme_product_import_admin_menu() {
    add_menu_page(
        'به‌روزرسانی قیمت و موجودی',
        'قیمت و موجودی',
        my_theme_product_import_capability(),
        'product-price-stock-import',
        'my_theme_product_import_page',
        'dashicons-update',
        56
    );
}
add_action('admin_menu', 'my_theme_product_import_admin_menu');

function my_theme_product_import_page() {
    if (! my_theme_product_import_can_manage()) {
        wp_die(esc_html__('You are not allowed to access this page.', 'my-theme'));
    }

    $token  = isset($_GET['import_token']) ? sanitize_key(wp_unslash($_GET['import_token'])) : '';
    $report = isset($_GET['report_token']) ? sanitize_key(wp_unslash($_GET['report_token'])) : '';
    $error  = isset($_GET['import_error']) ? sanitize_text_field(wp_unslash($_GET['import_error'])) : '';
    $batch  = $token ? get_transient('my_theme_product_import_' . get_current_user_id() . '_' . $token) : false;
    $result = $report ? get_transient('my_theme_product_report_' . get_current_user_id() . '_' . $report) : false;
    $backup = get_option('my_theme_product_import_last_backup_' . get_current_user_id());
    ?>
    <div class="wrap my-theme-product-import" dir="rtl">
        <h1>به‌روزرسانی قیمت و موجودی</h1>
        <p>فایل Excel باید ستون کد محصول و حداقل یکی از ستون‌های قیمت یا موجودی را داشته باشد. ستون‌های اضافی نادیده گرفته می‌شوند.</p>

        <?php if ($error) : ?>
            <div class="notice notice-error"><p><?php echo esc_html($error); ?></p></div>
        <?php endif; ?>

        <?php if (! class_exists('WooCommerce')) : ?>
            <div class="notice notice-error"><p>برای استفاده از این بخش باید افزونه ووکامرس فعال باشد.</p></div>
        <?php else : ?>
            <div class="my-theme-import-card">
                <form method="post" enctype="multipart/form-data" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                    <input type="hidden" name="action" value="my_theme_preview_product_import">
                    <?php wp_nonce_field('my_theme_preview_product_import'); ?>
                    <label for="my-theme-product-file"><strong>فایل Excel</strong></label>
                    <input id="my-theme-product-file" name="product_file" type="file" accept=".xls,.xlsx" required>
                    <p class="description">فرمت‌های مجاز: .xls و .xlsx — قیمت و موجودی می‌توانند جداگانه یا هم‌زمان به‌روزرسانی شوند — حداکثر حجم: ۱۰ مگابایت</p>
                    <?php submit_button('بررسی فایل و نمایش پیش‌نمایش', 'primary', 'submit', false); ?>
                </form>
            </div>

            <div class="my-theme-import-help">
                <h2>عنوان‌های قابل قبول ستون‌ها</h2>
                <table class="widefat striped">
                    <thead><tr><th>اطلاعات</th><th>عنوان‌های قابل قبول</th></tr></thead>
                    <tbody>
                        <tr><td>کد محصول (SKU)</td><td>کد محصول، کد کالا، کد، SKU، Product Code و شکل‌های رایج فارسی/عربی</td></tr>
                        <tr><td>قیمت</td><td>قیمت، قیمت محصول، قیمت اصلی، Price، Regular Price</td></tr>
                        <tr><td>موجودی</td><td>موجودی، موجودی کالا، تعداد موجودی، Stock، Stock Quantity، Quantity</td></tr>
                    </tbody>
                </table>
            </div>

            <?php if (is_array($batch)) : ?>
                <?php my_theme_product_import_render_preview($batch, $token); ?>
            <?php endif; ?>

            <?php if (is_array($result)) : ?>
                <?php my_theme_product_import_render_result($result); ?>
            <?php endif; ?>

            <?php if (is_array($backup) && ! empty($backup['products'])) : ?>
                <div class="my-theme-import-card">
                    <h2>بازگردانی آخرین به‌روزرسانی</h2>
                    <p>نسخه پشتیبان مربوط به <?php echo esc_html($backup['created_at']); ?> است و شامل <?php echo esc_html(count($backup['products'])); ?> محصول می‌شود.</p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" onsubmit="return confirm('مقادیر قیمت و موجودی آخرین عملیات بازگردانی شود؟');">
                        <input type="hidden" name="action" value="my_theme_rollback_product_import">
                        <?php wp_nonce_field('my_theme_rollback_product_import'); ?>
                        <?php submit_button('بازگردانی آخرین عملیات', 'secondary', 'submit', false); ?>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    <style>
        .my-theme-product-import{max-width:1100px}.my-theme-import-card,.my-theme-import-help,.my-theme-import-preview,.my-theme-import-result{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:20px;margin:18px 0}.my-theme-import-card input[type=file]{display:block;margin:14px 0;min-width:360px}.my-theme-product-import table{margin-top:12px}.my-theme-product-import th,.my-theme-product-import td{text-align:right}.my-theme-import-summary{display:flex;gap:12px;flex-wrap:wrap;margin:14px 0}.my-theme-import-count{padding:8px 12px;border-radius:6px;background:#f0f0f1}.my-theme-import-ok{color:#116329}.my-theme-import-bad{color:#b32d2e}.my-theme-import-actions{display:flex;align-items:center;gap:12px;margin-top:18px}.my-theme-import-scroll{max-height:430px;overflow:auto;border:1px solid #dcdcde}.my-theme-import-scroll table{border:0;margin:0}.my-theme-import-scroll thead th{position:sticky;top:0;background:#f6f7f7;z-index:1}
    </style>
    <?php
}

function my_theme_product_import_render_preview($batch, $token) {
    $valid_count = count($batch['valid']);
    $error_count = count($batch['errors']);
    ?>
    <div class="my-theme-import-preview">
        <h2>پیش‌نمایش تغییرات</h2>
        <div class="my-theme-import-summary">
            <span class="my-theme-import-count">ردیف‌های قابل اعمال: <strong class="my-theme-import-ok"><?php echo esc_html($valid_count); ?></strong></span>
            <span class="my-theme-import-count">ردیف‌های نامعتبر یا پیدا نشده: <strong class="my-theme-import-bad"><?php echo esc_html($error_count); ?></strong></span>
        </div>
        <?php if ($valid_count) : ?>
            <div class="my-theme-import-scroll"><table class="widefat striped">
                <thead><tr><th>ردیف</th><th>کد محصول</th><th>محصول</th><th>قیمت فعلی</th><th>قیمت جدید</th><th>موجودی فعلی</th><th>موجودی جدید</th></tr></thead>
                <tbody>
                <?php foreach ($batch['valid'] as $row) : ?>
                    <tr><td><?php echo esc_html($row['row']); ?></td><td dir="ltr"><?php echo esc_html($row['sku']); ?></td><td><?php echo esc_html($row['name']); ?></td><td><?php echo esc_html($row['old_price']); ?></td><td><?php echo esc_html($row['update_price'] ? $row['price'] : 'بدون تغییر'); ?></td><td><?php echo esc_html($row['old_stock']); ?></td><td><?php echo esc_html($row['update_stock'] ? $row['stock'] : 'بدون تغییر'); ?></td></tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php endif; ?>
        <?php if ($error_count) : ?>
            <h3>ردیف‌هایی که اعمال نمی‌شوند</h3>
            <div class="my-theme-import-scroll"><table class="widefat striped">
                <thead><tr><th>ردیف</th><th>کد محصول</th><th>علت</th></tr></thead>
                <tbody><?php foreach ($batch['errors'] as $row) : ?><tr><td><?php echo esc_html($row['row']); ?></td><td dir="ltr"><?php echo esc_html($row['sku']); ?></td><td class="my-theme-import-bad"><?php echo esc_html($row['message']); ?></td></tr><?php endforeach; ?></tbody>
            </table></div>
        <?php endif; ?>
        <?php if ($valid_count) : ?>
            <form class="my-theme-import-actions" method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
                <input type="hidden" name="action" value="my_theme_apply_product_import">
                <input type="hidden" name="import_token" value="<?php echo esc_attr($token); ?>">
                <?php wp_nonce_field('my_theme_apply_product_import_' . $token); ?>
                <?php submit_button('تأیید و اعمال تغییرات', 'primary', 'submit', false); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=product-price-stock-import')); ?>">لغو</a>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

function my_theme_product_import_render_result($result) {
    ?>
    <div class="my-theme-import-result">
        <h2>نتیجه به‌روزرسانی</h2>
        <div class="notice notice-success inline"><p><?php echo esc_html($result['updated']); ?> محصول با موفقیت به‌روزرسانی شد.</p></div>
        <?php if (! empty($result['failed'])) : ?><div class="notice notice-error inline"><p><?php echo esc_html(count($result['failed'])); ?> محصول هنگام ذخیره با خطا مواجه شد.</p></div><?php endif; ?>
        <?php if (! empty($result['skipped'])) : ?><p><?php echo esc_html($result['skipped']); ?> ردیف نامعتبر یا پیدا نشده اعمال نشد.</p><?php endif; ?>
    </div>
    <?php
}

function my_theme_product_import_redirect_error($message) {
    wp_safe_redirect(add_query_arg(['page' => 'product-price-stock-import', 'import_error' => $message], admin_url('admin.php')));
    exit;
}

function my_theme_product_import_preview_handler() {
    if (! my_theme_product_import_can_manage()) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'my-theme'));
    }
    check_admin_referer('my_theme_preview_product_import');

    if (! class_exists('WooCommerce')) {
        my_theme_product_import_redirect_error('افزونه ووکامرس فعال نیست.');
    }
    if (empty($_FILES['product_file']) || ! isset($_FILES['product_file']['error'])) {
        my_theme_product_import_redirect_error('فایلی انتخاب نشده است.');
    }
    $file = $_FILES['product_file'];
    if ((int) $file['error'] !== UPLOAD_ERR_OK) {
        my_theme_product_import_redirect_error('آپلود فایل کامل نشد. لطفاً دوباره تلاش کنید.');
    }
    if ((int) $file['size'] > 10 * MB_IN_BYTES) {
        my_theme_product_import_redirect_error('حجم فایل نباید بیشتر از ۱۰ مگابایت باشد.');
    }

    $extension = strtolower(pathinfo(sanitize_file_name($file['name']), PATHINFO_EXTENSION));
    if (! in_array($extension, ['xls', 'xlsx'], true)) {
        my_theme_product_import_redirect_error('فقط فایل‌های .xls و .xlsx پذیرفته می‌شوند.');
    }

    try {
        // Some accounting/export applications generate a valid XLSX package
        // but keep the legacy .xls filename. Detect the actual file signature
        // so those exports do not fail only because their extension is wrong.
        $signature = file_get_contents($file['tmp_name'], false, null, 0, 8);
        $is_xlsx = is_string($signature) && 0 === strpos($signature, "PK\x03\x04");
        $rows = $is_xlsx
            ? my_theme_product_import_read_xlsx($file['tmp_name'])
            : my_theme_product_import_read_xls($file['tmp_name']);
        $batch = my_theme_product_import_prepare_batch($rows);
    } catch (Exception $exception) {
        my_theme_product_import_redirect_error($exception->getMessage());
    }

    $token = wp_generate_password(20, false, false);
    set_transient('my_theme_product_import_' . get_current_user_id() . '_' . $token, $batch, 20 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg(['page' => 'product-price-stock-import', 'import_token' => $token], admin_url('admin.php')));
    exit;
}
add_action('admin_post_my_theme_preview_product_import', 'my_theme_product_import_preview_handler');

function my_theme_product_import_apply_handler() {
    if (! my_theme_product_import_can_manage()) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'my-theme'));
    }
    $token = isset($_POST['import_token']) ? sanitize_key(wp_unslash($_POST['import_token'])) : '';
    check_admin_referer('my_theme_apply_product_import_' . $token);
    $key   = 'my_theme_product_import_' . get_current_user_id() . '_' . $token;
    $batch = get_transient($key);
    if (! is_array($batch) || empty($batch['valid'])) {
        my_theme_product_import_redirect_error('پیش‌نمایش منقضی شده است. فایل را دوباره بارگذاری کنید.');
    }

    $updated = 0;
    $failed  = [];
    $backup  = [];
    foreach ($batch['valid'] as $row) {
        $product = wc_get_product((int) $row['product_id']);
        if (! $product || $product->get_sku() !== $row['sku']) {
            $failed[] = $row['sku'];
            continue;
        }
        try {
            $backup[] = [
                'product_id'   => $product->get_id(),
                'sku'          => $product->get_sku(),
                'regular_price'=> $product->get_regular_price('edit'),
                'sale_price'   => $product->get_sale_price('edit'),
                'manage_stock' => $product->get_manage_stock('edit'),
                'stock_quantity'=> $product->get_stock_quantity('edit'),
                'stock_status' => $product->get_stock_status('edit'),
            ];
            if (! empty($row['update_price'])) {
                $product->set_regular_price((string) $row['price']);
            }
            if (! empty($row['update_stock'])) {
                $product->set_manage_stock(true);
                $product->set_stock_quantity((int) $row['stock']);
                $product->set_stock_status(((int) $row['stock'] > 0) ? 'instock' : 'outofstock');
            }
            $product->save();
            wc_delete_product_transients($product->get_id());
            $updated++;
        } catch (Exception $exception) {
            $failed[] = $row['sku'];
        }
    }

    if ($backup) {
        update_option('my_theme_product_import_last_backup_' . get_current_user_id(), [
            'created_at' => current_time('mysql'),
            'products'   => $backup,
        ], false);
    }
    delete_transient($key);
    $report_token = wp_generate_password(20, false, false);
    set_transient('my_theme_product_report_' . get_current_user_id() . '_' . $report_token, [
        'updated' => $updated,
        'failed'  => $failed,
        'skipped' => count($batch['errors']),
    ], 20 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg(['page' => 'product-price-stock-import', 'report_token' => $report_token], admin_url('admin.php')));
    exit;
}
add_action('admin_post_my_theme_apply_product_import', 'my_theme_product_import_apply_handler');

function my_theme_product_import_rollback_handler() {
    if (! my_theme_product_import_can_manage()) {
        wp_die(esc_html__('You are not allowed to perform this action.', 'my-theme'));
    }
    check_admin_referer('my_theme_rollback_product_import');
    $option_key = 'my_theme_product_import_last_backup_' . get_current_user_id();
    $backup = get_option($option_key);
    if (! is_array($backup) || empty($backup['products'])) {
        my_theme_product_import_redirect_error('نسخه پشتیبانی برای بازگردانی وجود ندارد.');
    }
    $updated = 0;
    $failed = [];
    foreach ($backup['products'] as $old) {
        $product = wc_get_product((int) $old['product_id']);
        if (! $product || $product->get_sku() !== $old['sku']) {
            $failed[] = isset($old['sku']) ? $old['sku'] : '';
            continue;
        }
        try {
            $product->set_regular_price((string) $old['regular_price']);
            $product->set_sale_price((string) $old['sale_price']);
            $product->set_manage_stock((bool) $old['manage_stock']);
            $product->set_stock_quantity(null === $old['stock_quantity'] ? null : (int) $old['stock_quantity']);
            $product->set_stock_status((string) $old['stock_status']);
            $product->save();
            wc_delete_product_transients($product->get_id());
            $updated++;
        } catch (Exception $exception) {
            $failed[] = isset($old['sku']) ? $old['sku'] : '';
        }
    }
    delete_option($option_key);
    $report_token = wp_generate_password(20, false, false);
    set_transient('my_theme_product_report_' . get_current_user_id() . '_' . $report_token, [
        'updated' => $updated,
        'failed'  => $failed,
        'skipped' => 0,
    ], 20 * MINUTE_IN_SECONDS);
    wp_safe_redirect(add_query_arg(['page' => 'product-price-stock-import', 'report_token' => $report_token], admin_url('admin.php')));
    exit;
}
add_action('admin_post_my_theme_rollback_product_import', 'my_theme_product_import_rollback_handler');

function my_theme_product_import_prepare_batch($rows) {
    if (! is_array($rows) || ! $rows) {
        throw new Exception('فایل Excel خالی است یا قابل خواندن نیست.');
    }
    $header_index = null;
    foreach ($rows as $index => $row) {
        if (array_filter($row, static function ($value) { return '' !== trim((string) $value); })) {
            $header_index = $index;
            break;
        }
    }
    if (null === $header_index) {
        throw new Exception('فایل Excel خالی است.');
    }

    $map = [];
    foreach ($rows[$header_index] as $column => $title) {
        $field = my_theme_product_import_header_field($title);
        if ($field && ! isset($map[$field])) {
            $map[$field] = $column;
        }
    }
    if (! isset($map['sku'])) {
        throw new Exception('ستون ضروری کد محصول پیدا نشد.');
    }
    if (! isset($map['price']) && ! isset($map['stock'])) {
        throw new Exception('حداقل یکی از ستون‌های قیمت یا موجودی باید در فایل وجود داشته باشد.');
    }

    $valid = [];
    $errors = [];
    $seen = [];
    foreach (array_slice($rows, $header_index + 1, null, true) as $index => $row) {
        $sheet_row = $index + 1;
        $sku = my_theme_product_import_clean_sku(isset($row[$map['sku']]) ? $row[$map['sku']] : '');
        $raw_price = isset($map['price']) && isset($row[$map['price']]) ? trim((string) $row[$map['price']]) : '';
        $raw_stock = isset($map['stock']) && isset($row[$map['stock']]) ? trim((string) $row[$map['stock']]) : '';
        $update_price = '' !== $raw_price;
        $update_stock = '' !== $raw_stock;
        $price = $update_price ? my_theme_product_import_number($raw_price, false) : null;
        $stock = $update_stock ? my_theme_product_import_number($raw_stock, true) : null;
        if ('' === $sku && ! $update_price && ! $update_stock) {
            continue;
        }
        if ('' === $sku) {
            $errors[] = ['row' => $sheet_row, 'sku' => '', 'message' => 'کد محصول خالی است.'];
            continue;
        }
        if (isset($seen[$sku])) {
            $errors[] = ['row' => $sheet_row, 'sku' => $sku, 'message' => 'کد محصول در فایل تکراری است.'];
            continue;
        }
        $seen[$sku] = true;
        if (! $update_price && ! $update_stock) {
            continue;
        }
        if ($update_price && (null === $price || $price < 0)) {
            $errors[] = ['row' => $sheet_row, 'sku' => $sku, 'message' => 'قیمت معتبر نیست.'];
            continue;
        }
        if ($update_stock && null === $stock) {
            $errors[] = ['row' => $sheet_row, 'sku' => $sku, 'message' => 'موجودی باید یک عدد صحیح باشد؛ مقدار صفر و منفی نیز پذیرفته می‌شود.'];
            continue;
        }
        $product_id = wc_get_product_id_by_sku($sku);
        $product = $product_id ? wc_get_product($product_id) : false;
        if (! $product) {
            $errors[] = ['row' => $sheet_row, 'sku' => $sku, 'message' => 'محصولی با این کد (SKU) پیدا نشد.'];
            continue;
        }
        $valid[] = [
            'row'        => $sheet_row,
            'product_id' => $product->get_id(),
            'sku'        => $sku,
            'name'       => $product->get_name(),
            'old_price'  => $product->get_regular_price('edit'),
            'old_stock'  => null === $product->get_stock_quantity('edit') ? 'مدیریت نمی‌شود' : $product->get_stock_quantity('edit'),
            'update_price' => $update_price,
            'update_stock' => $update_stock,
            'price'      => $update_price ? (string) $price : null,
            'stock'      => $update_stock ? (int) $stock : null,
        ];
    }
    if (! $valid && ! $errors) {
        throw new Exception('بعد از ردیف عنوان، هیچ ردیف اطلاعاتی در فایل وجود ندارد.');
    }
    return ['valid' => $valid, 'errors' => $errors];
}

function my_theme_product_import_normalize_text($value) {
    $value = (string) $value;
    $value = preg_replace('/^\xEF\xBB\xBF/', '', $value);
    $value = strtr($value, ['ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', "\xE2\x80\x8C" => ' ', '_' => ' ', '-' => ' ']);
    $value = preg_replace('/[\x{200B}-\x{200F}\x{202A}-\x{202E}\x{2060}\x{FEFF}]/u', '', $value);
    $value = preg_replace('/\s+/u', ' ', trim($value));
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

function my_theme_product_import_header_field($title) {
    $title = my_theme_product_import_normalize_text($title);
    $aliases = [
        'sku' => ['کد محصول', 'کد کالا', 'کد', 'sku', 'product code', 'productcode'],
        'price' => ['قیمت', 'قیمت محصول', 'قیمت اصلی', 'price', 'regular price', 'regularprice'],
        'stock' => ['موجودی', 'موجودی کالا', 'تعداد موجودی', 'stock', 'stock quantity', 'stockquantity', 'quantity'],
    ];
    foreach ($aliases as $field => $values) {
        if (in_array($title, $values, true)) {
            return $field;
        }
    }
    return null;
}

function my_theme_product_import_digits($value) {
    return strtr((string) $value, [
        '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
        '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
    ]);
}

function my_theme_product_import_clean_sku($value) {
    $value = trim(my_theme_product_import_digits($value));
    if (preg_match('/^(-?\d+)\.0+$/', $value, $matches)) {
        $value = $matches[1];
    }
    return $value;
}

function my_theme_product_import_number($value, $integer) {
    if (is_int($value) || is_float($value)) {
        $number = (float) $value;
    } else {
        $value = my_theme_product_import_digits($value);
        $value = str_ireplace(['تومان', 'ریال', 'irr', 'irt'], '', $value);
        $value = str_replace(['٬', ',', ' ', "\xC2\xA0"], '', $value);
        $value = str_replace('٫', '.', trim($value));
        if ('' === $value || ! preg_match('/^-?\d+(?:\.\d+)?$/', $value)) {
            return null;
        }
        $number = (float) $value;
    }
    if (! is_finite($number) || ($integer && floor($number) !== $number)) {
        return null;
    }
    return $integer ? (int) $number : rtrim(rtrim(number_format($number, 6, '.', ''), '0'), '.');
}

function my_theme_product_import_read_xlsx($path) {
    if (! class_exists('ZipArchive') || ! class_exists('DOMDocument')) {
        throw new Exception('امکانات لازم برای خواندن XLSX روی سرور فعال نیست (Zip/XML).');
    }
    $zip = new ZipArchive();
    if (true !== $zip->open($path)) {
        throw new Exception('فایل XLSX باز نشد یا ساختار آن معتبر نیست.');
    }
    $shared = [];
    $shared_xml = $zip->getFromName('xl/sharedStrings.xml');
    if (false !== $shared_xml) {
        $dom = new DOMDocument();
        if (@$dom->loadXML($shared_xml)) {
            foreach ($dom->getElementsByTagName('si') as $item) {
                $text = '';
                foreach ($item->getElementsByTagName('t') as $part) {
                    $text .= $part->textContent;
                }
                $shared[] = $text;
            }
        }
    }
    $sheet_xml = $zip->getFromName('xl/worksheets/sheet1.xml');
    if (false === $sheet_xml) {
        $zip->close();
        throw new Exception('کاربرگ اول فایل XLSX پیدا نشد.');
    }
    $dom = new DOMDocument();
    if (! @$dom->loadXML($sheet_xml)) {
        $zip->close();
        throw new Exception('ساختار کاربرگ XLSX معتبر نیست.');
    }
    $rows = [];
    foreach ($dom->getElementsByTagName('row') as $row_node) {
        $row_number = max(1, (int) $row_node->getAttribute('r'));
        $row = [];
        foreach ($row_node->getElementsByTagName('c') as $cell) {
            $reference = $cell->getAttribute('r');
            preg_match('/^[A-Z]+/i', $reference, $matches);
            $column = my_theme_product_import_column_index(isset($matches[0]) ? $matches[0] : 'A');
            $type = $cell->getAttribute('t');
            $value = '';
            if ('inlineStr' === $type) {
                foreach ($cell->getElementsByTagName('t') as $text_node) {
                    $value .= $text_node->textContent;
                }
            } else {
                $values = $cell->getElementsByTagName('v');
                if ($values->length) {
                    $raw = $values->item(0)->textContent;
                    $value = ('s' === $type && isset($shared[(int) $raw])) ? $shared[(int) $raw] : $raw;
                }
            }
            $row[$column] = $value;
        }
        $rows[$row_number - 1] = $row;
    }
    $zip->close();
    ksort($rows);
    return $rows;
}

function my_theme_product_import_column_index($letters) {
    $index = 0;
    foreach (str_split(strtoupper($letters)) as $letter) {
        $index = ($index * 26) + (ord($letter) - 64);
    }
    return max(0, $index - 1);
}

function my_theme_product_import_read_xls($path) {
    $data = file_get_contents($path);
    if (false === $data || '' === $data) {
        throw new Exception('فایل XLS خالی است یا خوانده نشد.');
    }
    $trimmed = ltrim($data);
    if (0 === strpos($trimmed, '<?xml') || false !== stripos(substr($trimmed, 0, 500), '<Workbook')) {
        return my_theme_product_import_read_spreadsheetml($data);
    }
    if (substr($data, 0, 8) !== "\xD0\xCF\x11\xE0\xA1\xB1\x1A\xE1") {
        throw new Exception('ساختار فایل XLS معتبر نیست. لطفاً فایل را دوباره از Excel ذخیره کنید.');
    }
    $workbook = my_theme_product_import_xls_workbook_stream($data);
    return my_theme_product_import_parse_biff($workbook);
}

function my_theme_product_import_read_spreadsheetml($xml) {
    if (! class_exists('DOMDocument')) {
        throw new Exception('امکانات XML لازم برای خواندن فایل روی سرور فعال نیست.');
    }
    $dom = new DOMDocument();
    if (! @$dom->loadXML($xml)) {
        throw new Exception('ساختار فایل Excel XML معتبر نیست.');
    }
    $xpath = new DOMXPath($dom);
    $rows = [];
    foreach ($xpath->query('//*[local-name()="Worksheet"][1]//*[local-name()="Table"]/*[local-name()="Row"]') as $row_node) {
        $row = [];
        $column = 0;
        foreach ($xpath->query('./*[local-name()="Cell"]', $row_node) as $cell) {
            $index = $cell->getAttributeNS('urn:schemas-microsoft-com:office:spreadsheet', 'Index');
            if ($index) {
                $column = max(0, (int) $index - 1);
            }
            $nodes = $xpath->query('./*[local-name()="Data"]', $cell);
            $row[$column] = $nodes->length ? $nodes->item(0)->textContent : '';
            $column++;
        }
        $rows[] = $row;
    }
    return $rows;
}

function my_theme_product_import_u16($data, $offset) {
    $value = unpack('v', substr($data, $offset, 2));
    return $value ? $value[1] : 0;
}

function my_theme_product_import_u32($data, $offset) {
    $value = unpack('V', substr($data, $offset, 4));
    return $value ? (int) sprintf('%u', $value[1]) : 0;
}

function my_theme_product_import_xls_chain($data, $start, $fat, $sector_size) {
    $stream = '';
    $seen = [];
    $sector = $start;
    while ($sector < 0xFFFFFFF0 && isset($fat[$sector]) && ! isset($seen[$sector])) {
        $seen[$sector] = true;
        $stream .= substr($data, $sector_size + ($sector * $sector_size), $sector_size);
        $sector = $fat[$sector];
    }
    return $stream;
}

function my_theme_product_import_xls_workbook_stream($data) {
    $sector_size = 1 << my_theme_product_import_u16($data, 30);
    if ($sector_size < 512 || $sector_size > 4096) {
        throw new Exception('اندازه سکتور فایل XLS پشتیبانی نمی‌شود.');
    }
    $fat_sectors = [];
    for ($i = 0; $i < 109; $i++) {
        $sector = my_theme_product_import_u32($data, 76 + ($i * 4));
        if ($sector < 0xFFFFFFF0) {
            $fat_sectors[] = $sector;
        }
    }
    $difat_sector = my_theme_product_import_u32($data, 68);
    $difat_count = my_theme_product_import_u32($data, 72);
    for ($d = 0; $d < $difat_count && $difat_sector < 0xFFFFFFF0; $d++) {
        $chunk = substr($data, $sector_size + ($difat_sector * $sector_size), $sector_size);
        for ($i = 0; $i < ($sector_size / 4) - 1; $i++) {
            $sector = my_theme_product_import_u32($chunk, $i * 4);
            if ($sector < 0xFFFFFFF0) {
                $fat_sectors[] = $sector;
            }
        }
        $difat_sector = my_theme_product_import_u32($chunk, $sector_size - 4);
    }
    $fat = [];
    foreach ($fat_sectors as $fat_sector) {
        $chunk = substr($data, $sector_size + ($fat_sector * $sector_size), $sector_size);
        for ($i = 0; $i < $sector_size; $i += 4) {
            $fat[] = my_theme_product_import_u32($chunk, $i);
        }
    }
    $directory = my_theme_product_import_xls_chain($data, my_theme_product_import_u32($data, 48), $fat, $sector_size);
    $root = null;
    $book = null;
    for ($offset = 0; $offset + 128 <= strlen($directory); $offset += 128) {
        $entry = substr($directory, $offset, 128);
        $name_length = my_theme_product_import_u16($entry, 64);
        $name_raw = $name_length >= 2 ? substr($entry, 0, $name_length - 2) : '';
        $name = function_exists('iconv') ? @iconv('UTF-16LE', 'UTF-8//IGNORE', $name_raw) : '';
        $item = ['type' => ord($entry[66]), 'start' => my_theme_product_import_u32($entry, 116), 'size' => my_theme_product_import_u32($entry, 120)];
        if (5 === $item['type']) {
            $root = $item;
        }
        if (2 === $item['type'] && in_array($name, ['Workbook', 'Book'], true)) {
            $book = $item;
        }
    }
    if (! $book) {
        throw new Exception('جریان Workbook داخل فایل XLS پیدا نشد.');
    }
    $cutoff = my_theme_product_import_u32($data, 56);
    if ($book['size'] >= $cutoff) {
        return substr(my_theme_product_import_xls_chain($data, $book['start'], $fat, $sector_size), 0, $book['size']);
    }
    if (! $root) {
        throw new Exception('ساختار Mini Stream فایل XLS ناقص است.');
    }
    $mini_fat_stream = my_theme_product_import_xls_chain($data, my_theme_product_import_u32($data, 60), $fat, $sector_size);
    $mini_fat = [];
    for ($i = 0; $i + 4 <= strlen($mini_fat_stream); $i += 4) {
        $mini_fat[] = my_theme_product_import_u32($mini_fat_stream, $i);
    }
    $mini_stream = substr(my_theme_product_import_xls_chain($data, $root['start'], $fat, $sector_size), 0, $root['size']);
    $stream = '';
    $seen = [];
    $sector = $book['start'];
    while ($sector < 0xFFFFFFF0 && isset($mini_fat[$sector]) && ! isset($seen[$sector])) {
        $seen[$sector] = true;
        $stream .= substr($mini_stream, $sector * 64, 64);
        $sector = $mini_fat[$sector];
    }
    return substr($stream, 0, $book['size']);
}

function my_theme_product_import_biff_string($data, &$offset) {
    $length = my_theme_product_import_u16($data, $offset);
    $offset += 2;
    $flags = ord($data[$offset]);
    $offset++;
    $rich_runs = 0;
    $extended = 0;
    if ($flags & 0x08) {
        $rich_runs = my_theme_product_import_u16($data, $offset);
        $offset += 2;
    }
    if ($flags & 0x04) {
        $extended = my_theme_product_import_u32($data, $offset);
        $offset += 4;
    }
    $wide = (bool) ($flags & 0x01);
    $bytes = $length * ($wide ? 2 : 1);
    $raw = substr($data, $offset, $bytes);
    $offset += $bytes + ($rich_runs * 4) + $extended;
    if ($wide) {
        return function_exists('iconv') ? (string) @iconv('UTF-16LE', 'UTF-8//IGNORE', $raw) : '';
    }
    return function_exists('iconv') ? (string) @iconv('Windows-1252', 'UTF-8//IGNORE', $raw) : $raw;
}

function my_theme_product_import_parse_biff($data) {
    $sst = [];
    $sheet_offset = null;
    $offset = 0;
    $length = strlen($data);
    while ($offset + 4 <= $length) {
        $record = my_theme_product_import_u16($data, $offset);
        $size = my_theme_product_import_u16($data, $offset + 2);
        $payload = substr($data, $offset + 4, $size);
        if (0x0085 === $record && null === $sheet_offset) {
            $sheet_offset = my_theme_product_import_u32($payload, 0);
        } elseif (0x00FC === $record) {
            $sst_data = $payload;
            $next = $offset + 4 + $size;
            while ($next + 4 <= $length && 0x003C === my_theme_product_import_u16($data, $next)) {
                $continue_size = my_theme_product_import_u16($data, $next + 2);
                $continue_data = substr($data, $next + 4, $continue_size);
                // A CONTINUE record that splits SST character data starts with
                // a fresh Unicode compression flag. Product-import sheets use
                // short text values, so discarding that marker preserves the
                // logical shared-string stream across normal Excel splits.
                if ('' !== $continue_data && in_array(ord($continue_data[0]), [0, 1], true)) {
                    $continue_data = substr($continue_data, 1);
                }
                $sst_data .= $continue_data;
                $next += 4 + $continue_size;
            }
            $count = my_theme_product_import_u32($sst_data, 4);
            $position = 8;
            for ($i = 0; $i < $count && $position < strlen($sst_data); $i++) {
                $sst[] = my_theme_product_import_biff_string($sst_data, $position);
            }
        }
        $offset += 4 + $size;
    }
    if (null === $sheet_offset) {
        throw new Exception('کاربرگ فایل XLS پیدا نشد.');
    }
    $rows = [];
    $offset = $sheet_offset;
    while ($offset + 4 <= $length) {
        $record = my_theme_product_import_u16($data, $offset);
        $size = my_theme_product_import_u16($data, $offset + 2);
        $payload = substr($data, $offset + 4, $size);
        if (0x000A === $record) {
            break;
        }
        if (in_array($record, [0x00FD, 0x0203, 0x027E, 0x0204, 0x0006], true) && $size >= 6) {
            $row = my_theme_product_import_u16($payload, 0);
            $column = my_theme_product_import_u16($payload, 2);
            $value = '';
            if (0x00FD === $record) {
                $index = my_theme_product_import_u32($payload, 6);
                $value = isset($sst[$index]) ? $sst[$index] : '';
            } elseif (0x0203 === $record || 0x0006 === $record) {
                $number = unpack('d', substr($payload, 6, 8));
                $value = $number ? $number[1] : '';
            } elseif (0x027E === $record) {
                $value = my_theme_product_import_decode_rk(my_theme_product_import_u32($payload, 6));
            } elseif (0x0204 === $record) {
                $text_length = my_theme_product_import_u16($payload, 6);
                $value = substr($payload, 8, $text_length);
            }
            $rows[$row][$column] = $value;
        } elseif (0x00BD === $record && $size >= 12) {
            $row = my_theme_product_import_u16($payload, 0);
            $first = my_theme_product_import_u16($payload, 2);
            $last = my_theme_product_import_u16($payload, $size - 2);
            for ($column = $first; $column <= $last; $column++) {
                $rk_offset = 4 + (($column - $first) * 6) + 2;
                $rows[$row][$column] = my_theme_product_import_decode_rk(my_theme_product_import_u32($payload, $rk_offset));
            }
        }
        $offset += 4 + $size;
    }
    ksort($rows);
    return $rows;
}

function my_theme_product_import_decode_rk($rk) {
    $divide = (bool) ($rk & 0x01);
    if ($rk & 0x02) {
        $value = $rk >> 2;
        if ($value & 0x20000000) {
            $value -= 0x40000000;
        }
    } else {
        $high = $rk & 0xFFFFFFFC;
        $packed = pack('V2', 0, $high);
        $number = unpack('d', $packed);
        $value = $number ? $number[1] : 0;
    }
    return $divide ? $value / 100 : $value;
}
