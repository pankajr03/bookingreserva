<?php

defined('ABSPATH') or die();

use BookneticApp\Providers\Helpers\Helper;

/**
 * @var $parameters array
 */
$data = $parameters[ 'data' ] ?? [];
?>

<?php if (empty($data[ 'items' ])): ?>
    <div class="text-muted text-center"><?php echo bkntc__('Add-ons not found!'); ?></div>
    <?php return; ?>
<?php endif; ?>

<!-- Search result -->
<div class="addons_search_result <?php echo ! $parameters[ 'is_search' ] ? 'd-none' : ''; ?>">
    <?php echo bkntc__('%s results', [ '<span class="search_result">' . $data[ 'count' ] . '</span>' ], false) ?>
</div>

<div class="row addons_card_wrapper">
    <?php foreach ($data[ 'items' ] as $addon): ?>
        <div class="card_col col-xl-3 col-lg-4 col-md-6">
            <div class="addons_card">
                <div class="addons_card_content">
                    <div>
                        <div class="card_stats d-flex justify-content-space-between mb-3">
                            <img src="<?php echo $addon[ 'icon' ]; ?>" alt="<?php echo $addon[ 'name' ]; ?>">
                            <div class="d-flex align-items-center">
                                <?php if (!empty($addon['is_new'])): ?>
                                    <span class="booknetic_is_new_addon">New</span>
                                <?php endif; ?>

                                 <?php if ($addon[ 'released' ]): ?>
                                    <div class="card_price boostore_price d-flex ml-2">
                                        <?php if ($addon[ 'purchase_status' ] === 'owned'): ?>
                                        <?php elseif ($addon[ 'price' ][ 'current' ] === 0): ?>
                                            <span class="free"><?php echo bkntc__('Free'); ?></span>
                                        <?php else: ?>
                                            <?php if ($addon[ 'price' ][ 'current' ] < $addon[ 'price' ][ 'old' ]): ?>
                                                <span class="discount">$<?php echo round($addon[ 'price' ][ 'old' ], 1); ?></span>
                                            <?php endif; ?>
                                            <span>$<?php echo round($addon[ 'price' ][ 'current' ], 1); ?></span>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div>
                            <a href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=boostore&action=details&slug=<?php echo $addon[ 'slug' ]; ?>" class="d-inline-flex card_name mb-1">
                                <?php echo $addon[ 'name' ]; ?>
                            </a>
                            <span class="downloads">
                                <i class="fa fa-arrow-circle-down"></i>
                                <span><?php echo $addon[ 'downloads' ]; ?></span>
                            </span>
                        </div>
                    </div>

                    <div class="card_btns mt-3">
                        <?php if ($addon[ 'purchase_status' ] === 'unowned'): ?>
                            <?php if ($addon[ 'in_cart' ]): ?>
                                <a class="btn btn-lg view_cart_btn"
                                   href="admin.php?page=<?php echo Helper::getBackendSlug(); ?>&module=cart">
                                    <?php echo bkntc__('VIEW CART'); ?>
                                    <i class="fa fa-shopping-cart ml-2" aria-hidden="true"></i>
                                </a>
                            <?php else: ?>
                                <button class="btn btn-lg btn-add-to-cart"
                                        data-addon="<?php echo htmlspecialchars($addon[ 'slug' ]); ?>">
                                    <?php echo bkntc__('ADD TO CART'); ?>
                                </button>
                            <?php endif; ?>
                            <?php elseif (! empty($addon[ 'is_installed' ])): ?>
                                <button class="btn btn-lg btn-uninstall"
                                        data-addon="<?php echo htmlspecialchars($addon[ 'slug' ]); ?>">
                                    <?php echo bkntc__('UNINSTALL'); ?>
                                </button>
                        <?php elseif ($addon[ 'purchase_status' ] === 'owned'): ?>
                            <button class="btn btn-success btn-lg btn-install"
                                    data-addon="<?php echo htmlspecialchars($addon[ 'slug' ]); ?>">
                                <?php echo bkntc__('INSTALL'); ?>
                            </button>
                        <?php elseif (! $addon[ 'released' ]): ?>
                            <button class="btn btn-light-warning btn-lg">
                                <?php echo bkntc__('SOON'); ?>
                            </button>
                        <?php elseif (! empty($addon[ 'error_message' ])): ?>
                            <button class="btn btn-outline-danger btn-lg do_tooltip" data-placement="bottom"
                                    data-content="<?php echo htmlspecialchars($addon[ 'error_message' ]); ?>">
                                <i class="fa fa-exclamation-triangle pr-2"></i>
                                <?php echo bkntc__('CAN\'T INSTALL'); ?>
                            </button>

                        <?php elseif ($addon[ 'purchase_status' ] === 'pending'): ?>
                            <button class="btn btn-light-warning btn-lg">
                                <?php echo bkntc__('PENDING...'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Pagination -->
<div class="pagination row mt-4">
    <div class="col-md-12 d-flex flex-sm-row flex-column align-items-center justify-content-between">
        <div class="d-flex align-items-center mb-sm-0 mb-3">
                <span class="text-secondary mr-2 font-size-14">
                    <?php echo bkntc__('Showing %s of %s total', [ '<span class="pagination_current">' . $data[ 'cur_page' ] . '</span>', '<span class="pagination_total">' . $data[ 'pages' ] . '</span>' ], false) ?>
                </span>

            <div class="pagination_content">
                <?php
                $current = $data[ 'cur_page' ];
$total = $data[ 'pages' ];

if ($total <= 7) {
    $startPage = 2;
    $endPage = $total - 1;
} else {
    $startPage = $total - 1;
    $endPage = $startPage + 4;

    if ($startPage < 2) {
        $endPage += 2 - $startPage;
        $startPage = 2;
    }

    if ($endPage > $total - 1) {
        $startPage -= 1 - ($total - $endPage);
        $endPage = $total - 1;
    }
}
?>

                <span class="page_class badge <?php echo $current === 1 ? ' active_page badge-default' : ''; ?>">1</span><?php echo $startPage > 2 ? ' ... ' : ''; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <span class="page_class badge<?php echo $i === $current ? ' active_page badge-default' : ''; ?>"><?php echo $i; ?></span>
                <?php endfor; ?>

                <?php if ($total >= 2): ?>
                    <?php echo $total - 1 > $endPage ? ' ... ' : ''; ?><span
                    class="page_class badge<?php echo $total === $current ? ' active_page badge-default' : ''; ?>"><?php echo $total; ?></span>
                <?php endif; ?>
            </div>
        </div>

        <a href="<?php echo htmlspecialchars('https://www.booknetic.com/documentation/'); ?>" class="need_help_btn"
           target="_blank"><i class="far fa-question-circle"></i> <?php echo bkntc__('Need Help?'); ?></a>
    </div>
</div>
