<?php
/**
 * User: MHK
 * Date: 12 May 2022
 */

namespace ECommerce\EMailchimp;

use ECommerce\EMailchimp\Helpers\Helper;

abstract class EMailChimpStatus extends Helper
{
    const CLEANED = 'cleaned';
    const PENDING = 'pending';
    const SUBSCRIBED = 'subscribed';
    const UNSUBSCRIBED = 'unsubscribed';
}