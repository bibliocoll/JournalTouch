<nav class="top-bar" data-topbar role="navigation">
    <ul class="title-area">
        <li class="name">
            <h1><a href="#">JournalTouch Admin</a></h1>
        </li>
        <!-- Remove the class "menu-icon" to get rid of menu icon. Take out "Menu" to just have icon alone -->
        <li class="toggle-topbar menu-icon"><a href="#"><span>Menu</span></a></li>
    </ul>

    <section class="top-bar-section">
        <!-- Right Nav Section -->
        <ul class="right">
            <li class="">
                <a href="#">JT Version <?php echo $cfg->sys->current_jt_version ?></a></li>
            </li>
            <li class="has-dropdown">
                <a href="#"><?php echo __('Help') ?></a>
                <ul class="dropdown">
                    <li><a href="#">Not yet</a></li>
                    <li class="active"><a href="#">Not yet too</a></li>
                </ul>
            </li>
            <?php echo language_switch($cfg); ?>
        </ul>

        <!-- Left Nav Section -->
        <ul class="left">
            <li class=""><a href="index.php"><?php echo __('Home') ?></a></li>
            <li class=""><a href="settings.php"><?php echo __('Settings') ?></a></li>
            <li class=""><a href="updateTocs.php"><?php echo __('Update Journals') ?></a></li>
            <li class=""><a href="upgrade.php"><?php echo __('Upgrade JournalTouch') ?></a></li>
            <li class=""><a href="../index.php"><?php echo __('View Site') ?></a></li>
        </ul>
    </section>
</nav>
