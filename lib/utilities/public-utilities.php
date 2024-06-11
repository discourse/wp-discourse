<?php
/**
 * Public utility functions.
 *
 * @package WPDiscourse
 */

namespace WPDiscourse\Utilities;

use WPDiscourse\Shared\PluginUtilities;
use WPDiscourse\Shared\WebhookUtilities;

/**
 * Class PublicUtilities
 *
 * @package WPDiscourse
 */
class PublicUtilities {
    use PluginUtilities {
        get_options as public;
        validate as public;
        get_discourse_categories as public;
        get_discourse_user as public;
        get_discourse_user_by_email as public;
        sync_sso as public;
        discourse_request as public;
        get_api_credentials as public;
        get_sso_params as public;
    }

    use WebhookUtilities {
        get_discourse_webhook_data as public;
        verify_discourse_webhook_request as public;
    }
}
