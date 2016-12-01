<?php
/**
 * @OroScript("Update website URL")
 */

$configManager = $container->get('oro_config.global');
$url = $configManager->get('oro_ui.application_url');
$configManager->set('oro_website.url', $url);
$configManager->set('oro_website.secure_url', $url);
$configManager->flush();
