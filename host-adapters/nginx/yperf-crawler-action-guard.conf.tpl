# Yablonsky Performance — crawler/state-action origin guard.
# Useful public GET/HEAD remains crawlable. Only state-changing query URLs from
# known content crawlers are normalized to the clean content path before PHP.

set $yperf_is_content_crawler 0;
set $yperf_is_state_action 0;

if ($http_user_agent ~* "(bot|crawler|spider|ChatGPT-User|Claude-User|Perplexity-User|Amzn-User|meta-externalagent|Google-Extended)") {
    set $yperf_is_content_crawler 1;
}

if ($args ~* "(^|&)(add_to_wishlist|add-to-cart|add_to_cart|remove_item)=[^&]*") {
    set $yperf_is_state_action 1;
}

if ($args ~* "(^|&)action=yith-woocompare-add-product(&|$)") {
    set $yperf_is_state_action 1;
}

if ($args ~* "(^|&)wc-ajax=get_refreshed_fragments(&|$)") {
    set $yperf_is_state_action 1;
}

set $yperf_crawler_action_guard "${yperf_is_content_crawler}${yperf_is_state_action}";

if ($yperf_crawler_action_guard = "11") {
    return 301 $scheme://$host$uri;
}
