<?php
/**
 * WebEngine CMS — Modern Template Footer
 */
?>
<footer class="footer">
    <div class="footer-inner">
        <div class="footer-brand">
            <?php echo config('website_title', true); ?>
        </div>
        <div class="social-links">
            <?php if(check_value(config('social_link_facebook', true))): ?>
            <a href="<?php config('social_link_facebook'); ?>" target="_blank" title="Facebook">
                <i class="fa-brands fa-facebook-f"></i>
            </a>
            <?php endif; ?>
            <?php if(check_value(config('social_link_discord', true))): ?>
            <a href="<?php config('social_link_discord'); ?>" target="_blank" title="Discord">
                <i class="fa-brands fa-discord"></i>
            </a>
            <?php endif; ?>
            <?php if(check_value(config('social_link_instagram', true))): ?>
            <a href="<?php config('social_link_instagram'); ?>" target="_blank" title="Instagram">
                <i class="fa-brands fa-instagram"></i>
            </a>
            <?php endif; ?>
        </div>
        <div class="footer-copy">
            <?php $handler->webenginePowered(); ?> &copy; <?php echo date('Y'); ?>
        </div>
    </div>
</footer>