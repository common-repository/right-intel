<?php

add_action( 'admin_notices', function() use($storedVersion) {
    ?>
    <div class="updated">
        <p>Thank you for upgrading the Sharpr WordPress Plugin. $storedVersion is <?= $storedVersion ?></p>
    </div>
    <?php
});
