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
                <a href="#">Help</a>
                <ul class="dropdown">
                    <li><a href="#">Not yet</a></li>
                    <li class="active"><a href="#">Not yet too</a></li>
                </ul>
            </li>
            <?php echo language_switch($cfg); ?>
        </ul>

        <!-- Left Nav Section -->
        <ul class="left">
            <li class=""><a href="index.php">Home</a></li>
            <li class=""><a href="settings.php">Settings</a></li>
            <li class=""><a href="updateTocs.php">Update Journals</a></li>
            <li class=""><a href="upgrade.php">Upgrade JournalTouch</a></li>
        </ul>
    </section>
</nav>
