<?php
/**
 * @OroScript("Update Home Page Slider images paths")
 */

$container->get('oro_cms.migration.home_page_slider_images_source_fixer')->convertImagesPaths();
