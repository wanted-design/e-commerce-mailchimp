<?php
/**
 * User: MHK
 * Date: 09 May 2022
 */

namespace ECommerce\EMailchimp;

interface EMailchimpCompanyInterface
{
    /**
     * @return string
     */
    public function getAddress(): string;

    /**
     * @return string
     */
    public function getCampaignDefaultFromEmail(): string;

    /**
     * @return string
     */
    public function getCampaignDefaultFromName(): string;

    /**
     * @return string
     */
    public function getCampaignDefaultLanguage(): string;

    /**
     * @return string
     */
    public function getCity(): string;

    /**
     * @return string
     */
    public function getCountry(): string;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return string
     */
    public function getPhone(): string;

    /**
     * @return string
     */
    public function getState(): string;

    /**
     * @return string
     */
    public function getZip(): string;
}