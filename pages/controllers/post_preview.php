<?php

ob_end_clean(); // our template is an entire html page
$placeholderImageUrl = plugins_url("/img/placeholder-260.jpg", RI_BASE_PAGE);
$cssUrl = plugins_url("/css/" . Ri_Styling::getCssRelativeUrl($_GET), RI_BASE_PAGE);