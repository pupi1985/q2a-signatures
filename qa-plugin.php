<?php

if (!defined('QA_VERSION')) { // don't allow this page to be requested directly from browser
    header('Location: ../../');
    exit;
}

qa_register_plugin_module('module', 'qa-sig-admin.php', 'qa_signatures_admin', 'Signatures Admin');

qa_register_plugin_layer('qa-sig-layer.php', 'Signature Layer');

qa_register_plugin_overrides('qa-sig-overrides.php');

qa_register_plugin_phrases('qa-sig-lang-*.php', 'signature_plugin');
