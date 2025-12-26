<?php
if (!defined('ABSPATH')) exit;

class HMPC_UI {
    public function hooks() {
        add_action('wp_head', array($this, 'css_hide_google_translate_bar'), 999);
    }

    public function css_hide_google_translate_bar() {
        // Hides UI injected by translate.goog or Google translate overlays
        echo "<style>
            /* Google translate top bars / overlays */
            #gtx-trans,
            .gtx-trans,
            .gtx-trans-icon,
            .goog-te-banner-frame,
            .goog-te-balloon-frame,
            #goog-gt-tt,
            .goog-tooltip,
            .goog-tooltip:hover,
            .goog-text-highlight {
                display: none !important;
                visibility: hidden !important;
            }
            html, body { top: 0 !important; }
        </style>\n";
    }
}
