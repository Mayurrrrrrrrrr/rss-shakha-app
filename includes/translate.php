<?php
/**
 * Google Translate — reusable include
 * Add inside <head>: <?php include '../includes/translate.php'; ?>
 * Add before </body>: <?php include '../includes/translate_scripts.php'; ?>
 */
?>
<style>
    .goog-te-banner-frame {
        display: none !important;
    }

    body {
        top: 0 !important;
    }

    #translate-btn {
        position: fixed;
        bottom: 20px;
        right: 20px;
        z-index: 9999;
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 8px;
        padding: 6px 12px;
        font-size: 13px;
        cursor: pointer;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
        display: flex;
        align-items: center;
        gap: 6px;
        font-family: inherit;
    }

    #translate-btn:hover {
        background: #f5f5f5;
    }

    #google_translate_element {
        display: none;
    }
</style>