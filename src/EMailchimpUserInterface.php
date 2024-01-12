<?php
/**
 * User: MHK
 * Date: 09 May 2022
 */

namespace ECommerce\EMailchimp;

interface EMailchimpUserInterface
{
    /**
     * @return string
     */
    public function getEmail(): string;

    /**
     * @return string
     */
    public function getFirstName(): string;

    /**
     * @return string
     */
    public function getLastName(): string;
}