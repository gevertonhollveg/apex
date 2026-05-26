<?php
/**
 * WebEngine CMS — Modern Template
 * Based on WebEngine CMS 1.2.6
 *
 * @copyright (c) 2013-2025 Lautaro Angelico, All Rights Reserved
 * Licensed under the MIT license
 * http://opensource.org/licenses/MIT
 */

if(!defined('access') or !access) die();
include('inc/template.functions.php');

$serverInfoCache = LoadCacheData('server_info.cache');
if(is_array($serverInfoCache)) {
    $srvInfo = explode("|", $serverInfoCache[1][0]);
}

$maxOnline = config('maximum_online', true);
$onlinePlayers = isset($srvInfo[3]) ? $srvInfo[3] : 0;
$onlinePlayersPercent = check_value($maxOnline) ? $onlinePlayers*100/$maxOnline : 0;

if(!isset($_REQUEST['page'])) {
    $_REQUEST['page'] = '';
}
if(!isset($_REQUEST['subpage'])) {
    $_REQUEST['subpage'] = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php $handler->websiteTitle(); ?></title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&family=Cinzel:wght@400;700;900&display=swap" rel="stylesheet">

    <!-- Bootstrap 3 (required by WebEngine) -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- Template Styles -->
    <link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>style.css">
    <link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>override.css">
    <link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>castle-siege.css">
    <link rel="stylesheet" href="<?php echo __PATH_TEMPLATE_CSS__; ?>profiles.css">

    <!-- jQuery + Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>

    <link rel="icon" type="image/x-icon" href="<?php echo __PATH_TEMPLATE__; ?>favicon.ico">

    <script>var baseUrl = '<?php echo __BASE_URL__; ?>';</script>
</head>
<body>

<!-- ═══════════ NAVBAR ═══════════ -->
<nav class="navbar" id="mainNavbar">
    <div class="navbar-inner">
        <a href="<?php echo __BASE_URL__; ?>" class="navbar-brand">
            <?php echo config('website_title', true); ?>
        </a>
        <button class="mobile-toggle" id="mobileToggle" aria-label="Menu">
            <span></span><span></span><span></span>
        </button>
        <ul class="navbar-menu" id="navbarMenu">
            <?php templateBuildNavbar(); ?>
        </ul>
        <div class="navbar-right">
            <?php if(!isLoggedIn()): ?>
                <button class="btn-nav btn-nav-ghost" onclick="openAuthModal('login')">
                    <i class="fa-solid fa-right-to-bracket"></i> <?php echo lang('login_txt_1', true); ?>
                </button>
                <button class="btn-nav btn-nav-primary" onclick="openAuthModal('register')">
                    <i class="fa-solid fa-user-plus"></i> <?php echo lang('register_txt_1', true); ?>
                </button>
            <?php else: ?>
                <a href="<?php echo __BASE_URL__; ?>myaccount" class="btn-nav btn-nav-ghost">
                    <i class="fa-solid fa-user"></i> <?php echo lang('module_titles_txt_6', true); ?>
                </a>
                <a href="<?php echo __BASE_URL__; ?>logout" class="btn-nav btn-nav-ghost">
                    <i class="fa-solid fa-right-from-bracket"></i> <?php echo lang('login_txt_6', true); ?>
                </a>
            <?php endif; ?>
            <?php if(isLoggedIn() && $_SESSION['admin'] == true): ?>
                <a href="<?php echo __BASE_URL__; ?>admincp" class="btn-nav btn-nav-admin" target="_blank">
                    <i class="fa-solid fa-gear"></i> Admin
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ═══════════ HERO (Home only) ═══════════ -->
<?php if(empty($_REQUEST['page']) || $_REQUEST['page'] == 'home'): ?>
<section class="hero">
    <div class="hero-bg"></div>
    <div class="hero-overlay"></div>
    <div class="hero-particles" id="heroParticles"></div>
    <div class="hero-content">
        <div class="hero-badge animate-in animate-delay-1">
            <span class="dot"></span>
            <?php echo lang('sidebar_srvinfo_txt_5', true); ?>: <?php echo number_format($onlinePlayers); ?> Online
        </div>
        <h1 class="hero-title animate-in animate-delay-2"><?php echo config('website_title', true); ?></h1>
        <p class="hero-subtitle animate-in animate-delay-3">
            <?php echo config('website_meta_description', true); ?>
        </p>
        <div class="hero-actions animate-in animate-delay-4">
            <a href="<?php echo __BASE_URL__; ?>downloads" class="btn-hero btn-hero-primary">
                <i class="fa-solid fa-download"></i> Download Client
            </a>
            <?php if(!isLoggedIn()): ?>
            <button class="btn-hero btn-hero-ghost" onclick="openAuthModal('register')">
                <?php echo lang('register_txt_1', true); ?>
            </button>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<div class="stats-bar">
    <div class="stats-container">
        <?php if(isset($srvInfo) && is_array($srvInfo)): ?>
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($onlinePlayers); ?></div>
            <div class="stat-label"><?php echo lang('sidebar_srvinfo_txt_5', true); ?></div>
            <div class="online-bar"><div class="online-bar-fill" style="width:<?php echo round($onlinePlayersPercent); ?>%"></div></div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($srvInfo[0]); ?></div>
            <div class="stat-label"><?php echo lang('sidebar_srvinfo_txt_2', true); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($srvInfo[1]); ?></div>
            <div class="stat-label"><?php echo lang('sidebar_srvinfo_txt_3', true); ?></div>
        </div>
        <div class="stat-item">
            <div class="stat-value"><?php echo number_format($srvInfo[2]); ?></div>
            <div class="stat-label"><?php echo lang('sidebar_srvinfo_txt_4', true); ?></div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════ LOGIN/REGISTER MODAL ═══════════ -->
<?php if(!isLoggedIn()): ?>
<div class="modal-backdrop" id="modalBackdrop" onclick="closeAuthModal()"></div>
<div class="modal" id="authModal">
    <button class="modal-close" onclick="closeAuthModal()">&times;</button>
    <div class="modal-tabs">
        <button class="modal-tab active" id="tabLogin" onclick="switchAuthTab('login')"><?php echo lang('login_txt_1', true); ?></button>
        <button class="modal-tab" id="tabRegister" onclick="switchAuthTab('register')"><?php echo lang('register_txt_1', true); ?></button>
    </div>
    <!-- Login Form -->
    <div id="loginForm">
        <div class="modal-title"><?php echo lang('module_titles_txt_2', true); ?></div>
        <div class="modal-subtitle"><?php echo lang('login_txt_1', true); ?></div>
        <form action="<?php echo __BASE_URL__; ?>login" method="post">
            <div class="form-group">
                <label><?php echo lang('login_txt_7', true); ?></label>
                <input type="text" class="form-input" name="webengineLogin_user" placeholder="<?php echo lang('login_txt_7', true); ?>" required>
            </div>
            <div class="form-group">
                <label><?php echo lang('login_txt_8', true); ?></label>
                <input type="password" class="form-input" name="webengineLogin_pwd" placeholder="<?php echo lang('login_txt_8', true); ?>" required>
            </div>
            <input type="hidden" name="webengineLogin_submit" value="1">
            <?php templateRecaptchaV2(); ?>
            <button type="submit" class="btn-hero btn-hero-primary" style="width:100%;justify-content:center;margin-top:8px">
                <?php echo lang('login_txt_3', true); ?>
            </button>
            <div class="form-footer">
                <a href="<?php echo __BASE_URL__; ?>forgotpassword"><?php echo lang('login_txt_4', true); ?></a>
            </div>
        </form>
    </div>
    <!-- Register Form -->
    <div id="registerForm" style="display:none">
        <div class="modal-title"><?php echo lang('register_txt_1', true); ?></div>
        <div class="modal-subtitle"><?php echo lang('register_txt_2', true); ?></div>
        <form id="authRegisterForm" class="auth-ajax-register" action="<?php echo __BASE_URL__; ?>register" method="post" data-auth-tab="register">
            <div class="form-group">
                <label><?php echo lang('login_txt_7', true); ?></label>
                <input type="text" class="form-input" name="webengineRegister_user" placeholder="<?php echo lang('login_txt_7', true); ?>" required>
            </div>
            <div class="form-group">
                <label><?php echo lang('register_txt_9', true); ?></label>
                <input type="email" class="form-input" name="webengineRegister_email" placeholder="<?php echo lang('register_txt_9', true); ?>" required>
            </div>
            <div class="form-group">
                <label><?php echo lang('login_txt_8', true); ?></label>
                <input type="password" class="form-input" name="webengineRegister_pwd" placeholder="<?php echo lang('login_txt_8', true); ?>" required>
            </div>
            <div class="form-group">
                <label><?php echo lang('register_txt_8', true); ?></label>
                <input type="password" class="form-input" name="webengineRegister_pwdc" placeholder="<?php echo lang('register_txt_8', true); ?>" required>
            </div>
            <input type="hidden" name="webengineRegister_submit" value="1">
            <input type="hidden" name="webengineRegister_ajax" value="1">
            <?php templateRecaptchaV2(); ?>
            <button type="submit" class="btn-hero btn-hero-primary" style="width:100%;justify-content:center;margin-top:8px">
                <?php echo lang('register_txt_1', true); ?>
            </button>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- ═══════════ MAIN CONTENT ═══════════ -->
<div class="main-content">
    <div class="content-wrapper">
        <?php $handler->loadModule($_REQUEST['page'],$_REQUEST['subpage']); ?>
    </div>
</div>

<!-- ═══════════ FLOATING WIDGETS ═══════════ -->
<div class="floating-widgets">
    <!-- Events Schedule Button -->
    <button class="floating-btn floating-btn-events" id="eventsToggle" onclick="toggleEventsPopup()">
        <i class="fa-solid fa-calendar-days"></i>
        <span>Events Schedule</span>
    </button>
    <!-- Discord Button -->
    <?php if(check_value(config('social_link_discord', true))): ?>
    <a href="<?php echo config('social_link_discord', true); ?>" target="_blank" class="floating-btn floating-btn-discord">
        <i class="fa-brands fa-discord"></i>
        <span>Discord</span>
    </a>
    <?php else: ?>
    <a href="#" target="_blank" class="floating-btn floating-btn-discord">
        <i class="fa-brands fa-discord"></i>
        <span>Discord</span>
    </a>
    <?php endif; ?>
</div>

<!-- Events Popup Panel -->
<div class="events-popup" id="eventsPopup">
    <div class="events-popup-header">
        <span class="events-popup-title"><i class="fa-solid fa-calendar-days" style="margin-right:6px"></i>Events Schedule</span>
        <button class="events-popup-close" onclick="toggleEventsPopup()">&times;</button>
    </div>
    <div class="events-popup-body" id="eventsPopupBody">
        <!-- Populated by events.js -->
        <div style="padding:20px;text-align:center;color:var(--text-muted);font-size:13px;">Loading events...</div>
    </div>
</div>

<!-- ═══════════ FOOTER ═══════════ -->
<?php include('inc/modules/footer.php'); ?>

<!-- Template JS -->
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>main.js"></script>
<script src="<?php echo __PATH_TEMPLATE_JS__; ?>events.js"></script>

</body>
</html>