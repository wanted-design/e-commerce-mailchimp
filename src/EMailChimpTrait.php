<?php
/**
 * User: MHK
 * Date: 09 May 2022
 */

namespace ECommerce\EMailchimp;

use DrewM\MailChimp\MailChimp;
use ECommerce\EMailchimp\Helpers\MailChimpList;
use ECommerce\EMailchimp\Helpers\MailChimpMember;
use Illuminate\Support\Str;
use MailchimpMarketing\ApiClient;
use stdClass;

trait EMailChimpTrait
{
    protected static array $errors
        = [
            'type' => null,
            'title' => null,
            'status' => -1,
            'detail' => null,
            'instance' => null,
        ];
    /**
     * @var MailChimp
     */
    protected static $mailChimp;
    /**
     * @var ApiClient
     */
    protected static $officialMailChimp;

    /**
     * @param string $list_name
     * @param EMailchimpCompanyInterface $company
     * @param string $subject
     *
     * @return MailChimpList|stdClass|null
     */
    public static function firstOrCreateList(string $list_name, EMailchimpCompanyInterface $company, string $subject)
    {
        if (!self::listIsExist($list_name))
            $creation = self::createList($list_name, $company, $subject);
        return self::getListByName($list_name);
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public static function listIsExist(string $name): bool
    {
        return !!self::getListByName($name);
    }

    /**
     * @param string $name
     *
     * @return MailChimpList|stdClass|null
     */
    public static function getListByName(string $name)
    {
        return self::getListByAttribute('name', $name);
    }

    /**
     * @param string $attribute
     * @param $value
     *
     * @return MailChimpList|null
     */
    protected static function getListByAttribute(string $attribute, $value)
    {
        $list = self::getLists()->firstWhere($attribute, $value);
        /**
         * @var MailChimpList|stdClass $list
         */
        return $list ?? null;
    }

    /**
     * @return \Illuminate\Support\Collection|MailChimpList[]
     */
    public static function getLists()
    {
        $lists = self::getMailChimp()->get('lists')['lists'];
        return collect($lists)->map(fn($item) => self::arrayToObject($item));
    }

    /**
     * @return MailChimp
     */
    public static function getMailChimp(): MailChimp
    {
        if (is_null(self::$mailChimp)) self::$mailChimp = \Newsletter::getApi();
        return self::$mailChimp;
    }

    /**
     * @param array $array
     *
     * @return stdClass
     */
    protected static function arrayToObject($array)
    {
        $array = is_array($array) ? $array : [];
        return json_decode(json_encode($array));
    }

    /**
     * @param string $list_name
     * @param EMailchimpCompanyInterface $company
     * @param string $subject
     *
     * @return MailChimpList|stdClass|null
     */
    public static function createList(string $list_name, EMailchimpCompanyInterface $company, string $subject)
    {
        self::requestStarted();
        $response = self::getMailChimp()->post('lists', self::listAttributes($list_name, $company, $subject));
        return self::requestEndedWithoutError($response) ? self::arrayToObject($response) : null;
    }

    protected static function requestStarted()
    {
        self::$errors = [
            'type' => null,
            'title' => null,
            'status' => -1,
            'detail' => null,
            'instance' => null,
        ];
    }

    /**
     * @param string $list_name
     * @param EMailchimpCompanyInterface $company
     * @param string $subject
     *
     * @return array
     */
    protected static function listAttributes(string $list_name, EMailchimpCompanyInterface $company, string $subject): array
    {
        return [
            'name' => $list_name,
            'permission_reminder' => 'permission_reminder',
            'email_type_option' => true,
            'contact' => array_merge(
                [
                    'company' => $company->getName(),
                    'address1' => $company->getAddress(),
                    'city' => $company->getCity(),
                    'country' => $company->getCountry(),
                    'zip' => $company->getZip(),
                    'state' => $company->getState(),

                ],
                \Str::length($company->getPhone()) ? [] : ['phone' => $company->getPhone()]
            ),
            'campaign_defaults' => [
                'from_name' => $company->getCampaignDefaultFromName(),
                'from_email' => $company->getCampaignDefaultFromEmail(),
                'subject' => $subject,
                'language' => $company->getCampaignDefaultLanguage(),
            ],
        ];
    }

    /**
     * @param array $response
     *
     * @return bool
     */
    protected static function requestEndedWithoutError($response): bool
    {
        $response = is_array($response) ? $response : [];
        if (isset($response['type']) && ($response['type'] == 'https://mailchimp.com/developer/marketing/docs/errors/')) {
            self::$errors = $response;
            return false;
        } elseif (!empty($response['errors'])) {
            self::$errors = $response;
            return false;
        }
        return true;
    }

    /**
     * @return string
     */
    public static function getErrorsToString(): string
    {
        return self::getErrors()['status'] == -1 ? '' : ('[error ' . self::getErrors()['status'] . '] ' . self::getErrors()['title'] . ' : ' . self::getErrors()['detail']);
    }

    /**
     * @return array{type:string,title:string,status:int,detail:string,instance:string}
     */
    public static function getErrors(): array
    {
        return self::$errors;
    }

    /**
     * @param $list
     *
     * @return string
     */
    public static function getListAdminUrl(stdClass $list): string
    {
        /**
         * @var MailChimpList|stdClass $list
         */
        return "https://admin.mailchimp.com/lists/members?id={$list->web_id}";
    }

    /**
     * @param string $id
     *
     * @return MailChimpList|stdClass|null
     */
    public static function getListById(string $id)
    {
        self::requestStarted();
        $list = self::getMailChimp()->get("lists/{$id}");
        return self::requestEndedWithoutError($list) ? self::arrayToObject($list) : null;
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::subscribeOrUpdate
     */
    public static function subscribeOrUpdate(EMailchimpUserInterface $user, stdClass $list): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        if (!self::hasMember($user, $list)) return self::subscribe($user, $list);
        $hash = self::getSubscriberHash($user->getEmail());
        $response = self::getMailChimp()->put("lists/{$list->id}/members/{$hash}", self::memberAttributes($user, true));
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::hasMember
     */
    public static function hasMember(EMailchimpUserInterface $user, stdClass $list): bool
    {
        /**
         * @var MailChimpList|stdClass $list
         */
        $member = self::getMember($user, $list);
        return $member && (Str::lower($member->email_address) == Str::lower($user->getEmail()));
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return MailChimpMember|stdClass|null
     * @link \Spatie\Newsletter\Newsletter::getMember
     * @link https://mailchimp.com/developer/marketing/api/list-members/get-member-info/
     */
    public static function getMember(EMailchimpUserInterface $user, stdClass $list)
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        $hash = self::getSubscriberHash($user->getEmail());
        $member = self::getMailChimp()->get("lists/{$list->id}/members/{$hash}");
        return self::requestEndedWithoutError($member) ? self::arrayToObject($member) : null;
    }

    /**
     * @param string $email
     *
     * @return string
     * @link \Spatie\Newsletter\Newsletter::getSubscriberHash
     */
    protected static function getSubscriberHash(string $email): string
    {
        return self::getMailChimp()->subscriberHash($email);
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::subscribe
     */
    public static function subscribe(EMailchimpUserInterface $user, stdClass $list): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        $response = self::getMailChimp()->post("lists/{$list->id}/members", self::memberAttributes($user, true));
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param bool $subscribed
     * @param string $language
     *
     * @return array
     */
    protected static function memberAttributes(EMailchimpUserInterface $user, bool $subscribed, string $language = 'fr'): array
    {
        return self::setMemberAttributes($user->getEmail(), $user->getFirstName(), $user->getLastName(), $subscribed ? EMailChimpStatus::SUBSCRIBED : EMailChimpStatus::UNSUBSCRIBED, $language);
    }

    /**
     * @param string $email_address
     * @param string $FNAME
     * @param string $LNAME
     * @param string $status
     * @param string $language
     *
     * @param string $email_type
     *
     * @return array
     */
    protected static function setMemberAttributes(string $email_address, string $FNAME, string $LNAME, string $status, string $language = 'fr', string $email_type = 'html'): array
    {
        return [
            'email_address' => $email_address,
            /**
             * 'html' | 'text'
             */
            'email_type' => $email_type,
            /**
             * 'subscribed' | 'unsubscribed' | 'cleaned' | 'pending'
             */
            'status' => $status,
            'merge_fields' => [
                'FNAME' => $FNAME,
                'LNAME' => $LNAME,
            ],
            //'interests' => [],
            /**
             * @link https://mailchimp.com/help/view-and-edit-contact-languages/
             * 'en' | 'ar' | 'fr' | 'nl'
             */
            'language' => $language,
            /**
             * true | false
             */
            //'vip' => false,
            //'location' => ['latitude' => 0, 'longitude' => 0,],
            //'ip_signup' => '',
            //'timestamp_signup' => '',
            //'ip_opt' => '',
            //'timestamp_opt' => '',
        ];
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::unsubscribe
     */
    public
    static function unsubscribe(EMailchimpUserInterface $user, stdClass $list): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        if (!self::hasMember($user, $list)) return true;
        $hash = self::getSubscriberHash($user->getEmail());
        $response = self::getMailChimp()->patch("lists/{$list->id}/members/{$hash}", ['status' => EMailChimpStatus::UNSUBSCRIBED]);
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param string $email
     * @param stdClass $list
     *
     * @return bool
     * @link https://mailchimp.com/developer/marketing/api/list-members/update-list-member/
     */
    public
    static function unsubscribeByEmail(string $email, stdClass $list): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        if (!self::hasMemberByEmail($email, $list)) return true;
        $hash = self::getSubscriberHash($email);
        $response = self::getMailChimp()->patch("lists/{$list->id}/members/{$hash}", ['status' => EMailChimpStatus::UNSUBSCRIBED]);
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param string $email
     * @param stdClass $list
     *
     * @return bool
     * @link MailChimpController::hasMember
     */
    public
    static function hasMemberByEmail(string $email, stdClass $list): bool
    {
        /**
         * @var MailChimpList|stdClass $list
         */
        $member = self::getMemberByEmail($email, $list);
        return $member && (Str::lower($member->email_address) == Str::lower($email));
    }

    /**
     * @param string $email
     * @param stdClass $list
     *
     * @return MailChimpMember|stdClass|null
     * @link MailChimpController::getMember
     * @link https://mailchimp.com/developer/marketing/api/list-members/get-member-info/
     */
    public
    static function getMemberByEmail(string $email, stdClass $list)
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        $hash = self::getSubscriberHash($email);
        $member = self::getMailChimp()->get("lists/{$list->id}/members/{$hash}");
        return self::requestEndedWithoutError($member) ? self::arrayToObject($member) : null;
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     * @param string $old_email
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::updateEmailAddress
     */
    public
    static function updateEmailAddress(EMailchimpUserInterface $user, stdClass $list, string $old_email): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        if (self::hasMemberByEmail($old_email, $list)) return false;
        $hash = self::getSubscriberHash($old_email);
        $response = self::getMailChimp()->patch("lists/{$list->id}/members/{$hash}", ['email_address' => $user->getEmail()]);
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param stdClass $list
     * @param string $list_new_name
     * @param EMailchimpCompanyInterface $company
     *
     * @return bool
     */
    public
    static function updateListName(stdClass $list, string $list_new_name, EMailchimpCompanyInterface $company): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        $response = self::getMailChimp()->patch("/lists/{$list->id}", self::listAttributes($list_new_name, $company, $list->campaign_defaults->subject));
        return self::requestEndedWithoutError($response);
    }

    /**
     * @param stdClass $list
     * @param string $target_status
     *
     * @return bool
     * @link https://mailchimp.com/developer/marketing/api/lists/batch-subscribe-or-unsubscribe/
     */
    public
    static function updateStatusAllListMembers(stdClass $list, string $target_status): bool
    {
        self::requestStarted();
        /**
         * @var MailChimpList|stdClass $list
         */
        if (!self::validateStatus($target_status, $errors)) {
            self::$errors = $errors;
            return false;
        }
        $response = self::getMailChimp()->post("lists/{$list->id}", [
            'skip_merge_validation' => true,
            'skip_duplicate_check' => true,
            'update_existing' => true,
            'members' => self::getMembers($list)
                ->filter(fn($member) => $member->status != $target_status)
                ->map(function ($member) use ($target_status) {
                    if (0) return [
                        'email_address' => $member->email_address,
                        'status' => $target_status,
                    ];
                    return self::setMemberAttributes(
                        $member->email_address,
                        $member->merge_fields->FNAME,
                        $member->merge_fields->LNAME,
                        $target_status
                    );
                })->values()
                ->toArray(),
        ]);
        return is_bool($response) ? $response : self::requestEndedWithoutError($response);
    }

    /**
     * @param string $status
     * @param array|null $errors
     *
     * @return bool
     */
    protected static function validateStatus(?string $status, ?array &$errors = []): bool
    {
        $valid = in_array($status, [EMailChimpStatus::SUBSCRIBED, EMailChimpStatus::UNSUBSCRIBED, EMailChimpStatus::CLEANED, EMailChimpStatus::PENDING]);
        if (!$valid) {
            $errors = [
                'type' => 'https://mailchimp.com/developer/marketing/docs/errors/',
                'title' => 'Invalid Status',
                'status' => 400,
                'detail' => 'Data presented is not one of the accepted values: subscribed, unsubscribed, cleaned, pending.',
                'instance' => null,
            ];
        } else {
            $errors = [
                'type' => null,
                'title' => null,
                'status' => -1,
                'detail' => null,
                'instance' => null,
            ];
        }
        return $valid;
    }

    /**
     * @param stdClass $list
     * @param string|null $status
     *
     * @return \Illuminate\Support\Collection|MailChimpMember[]
     */
    public
    static function getMembers(stdClass $list, ?string $status = null)
    {
        /**
         * @var MailChimpList|stdClass $list
         */
        $members = self::getMailChimp()->get("lists/{$list->id}/members", array_merge(
                [
                    'count' => 1000,
                ],
                self::validateStatus($status) ? compact('status') : []
            )
        );
        $members = is_array($members) && isset($members['members']) ? $members['members'] : [];
        return collect($members)->map(fn($item) => self::arrayToObject($item));
    }

    /**
     * @return ApiClient
     */
    public static function getOfficialMailChimp()
    {
        if (is_null(self::$officialMailChimp)) self::$officialMailChimp = new ApiClient();
        self::$officialMailChimp->setConfig([
            'apiKey' => config('newsletter.apiKey'),
            'server' => self::getServerPrefix(),
        ]);
        return self::$officialMailChimp;
    }

    /**
     * @return string
     */
    protected static function getServerPrefix()
    {
        if (preg_match('/^[0-9a-f]{32}-(\w+)$/i', config('newsletter.apiKey'), $matches))
            return $matches[1];
        throw new \InvalidArgumentException("config('newsletter.apiKey')");
    }

    /**
     * @param EMailchimpUserInterface $user
     * @param stdClass $list
     *
     * @return bool
     * @link \Spatie\Newsletter\Newsletter::isSubscribed
     */
    public
    function isSubscribed(EMailchimpUserInterface $user, stdClass $list): bool
    {
        /**
         * @var MailChimpList|stdClass $list
         */
        $member = self::getMember($user, $list);
        return $member && ($member->status == EMailChimpStatus::SUBSCRIBED);
    }
}
