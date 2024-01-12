### 1. composer.json

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:wanted-design/e-commerce-mailchimp.git",
            "or url": "https://username:token@github.com:wanted-design/e-commerce-mailchimp.git"
        }
    ]
}
```

### 2. Installation

```bash
composer require "wanted-design/e-commerce-mailchimp"
```

### 3. To publish the config file to `config/newsletter.php` run:

```bash
php artisan vendor:publish --provider="Spatie\Newsletter\NewsletterServiceProvider"
```

### 4. Update the following in your `.env` from [Mailchimp API](https://admin.mailchimp.com/account/api/):

  ```dotenv
      MAILCHIMP_APIKEY=
  ```

### 5. Implement Company Model

```php
namespace App\Models;

use ECommerce\EMailchimp\EMailchimpCompanyInterface;

class Company implements EMailchimpCompanyInterface
{
    public function getAddress(): string
    {
        // TODO: Implement getAddress() method.
    }

    public function getCampaignDefaultFromEmail(): string
    {
        // TODO: Implement getCampaignDefaultFromEmail() method.
    }

    public function getCampaignDefaultFromName(): string
    {
        // TODO: Implement getCampaignDefaultFromName() method.
    }

    public function getCampaignDefaultLanguage(): string
    {
        // TODO: Implement getCampaignDefaultLanguage() method.
    }

    public function getCity(): string
    {
        // TODO: Implement getCity() method.
    }

    public function getCountry(): string
    {
        // TODO: Implement getCountry() method.
    }

    public function getName(): string
    {
        // TODO: Implement getName() method.
    }

    public function getPhone(): string
    {
        // TODO: Implement getPhone() method.
    }

    public function getState(): string
    {
        // TODO: Implement getState() method.
    }

    public function getZip(): string
    {
        // TODO: Implement getZip() method.
    }
}
```

### 6. Implement User Model

```php
namespace App\Models;

use ECommerce\EMailchimp\EMailchimpUserInterface;

class User implements EMailchimpUserInterface
{
    public function getEmail(): string
    {
        // TODO: Implement getEmail() method.
    }

    public function getFirstName(): string
    {
        // TODO: Implement getFirstName() method.
    }

    public function getLastName(): string
    {
        // TODO: Implement getLastName() method.
    }
}
```

### 7. Usage

```php
use ECommerce\EMailchimp\EMailChimpTrait;
use ECommerce\EMailchimp\EMailchimpCompanyInterface;
use ECommerce\EMailchimp\EMailchimpUserInterface;


EMailChimpTrait::createList(string $list_name, EMailchimpCompanyInterface::class $company,string $subject)
EMailChimpTrait::firstOrCreateList(string $list_name, EMailchimpCompanyInterface::class $company,string $subject)
EMailChimpTrait::getErrors(): array
EMailChimpTrait::getErrorsToString(): string
EMailChimpTrait::getListAdminUrl(stdClass::class $list): string
EMailChimpTrait::getListById(string $id)
EMailChimpTrait::getListByName(string $name)
EMailChimpTrait::getLists()
EMailChimpTrait::getMailChimp(): \DrewM\MailChimp\MailChimp::class
EMailChimpTrait::getMember(EMailchimpUserInterface::class $user, stdClass::class $list)
EMailChimpTrait::getMemberByEmail(string $email, stdClass::class $list)
EMailChimpTrait::getMembers(stdClass::class $list)
EMailChimpTrait::hasMember(EMailchimpUserInterface::class $user, stdClass::class $list): bool
EMailChimpTrait::hasMemberByEmail(string $email, stdClass::class $list): bool
EMailChimpTrait::isSubscribed(EMailchimpUserInterface::class $user, stdClass::class $list): bool
EMailChimpTrait::listIsExist(string $name): bool
EMailChimpTrait::subscribe(EMailchimpUserInterface::class $user, stdClass::class $list): bool
EMailChimpTrait::subscribeOrUpdate(EMailchimpUserInterface::class $user, stdClass::class $list): bool
EMailChimpTrait::unsubscribe(EMailchimpUserInterface::class $user, stdClass::class $list): bool
EMailChimpTrait::unsubscribeByEmail(string $email, stdClass::class $list): bool
EMailChimpTrait::updateEmailAddress(EMailchimpUserInterface::class $user, stdClass::class $list, string $old_email): bool
EMailChimpTrait::updateListName(stdClass::class $list, string $list_new_name, EMailchimpCompanyInterface::class $company): bool
EMailChimpTrait::updateStatusAllListMembers(stdClass::class $list, string $target_status): bool

// Official Client Library
EMailChimpTrait::getOfficialMailChimp(): \MailchimpMarketing\ApiClient::class


// OR

class Any
{
    use EMailChimpTrait;

    function foo(string $bar)
    {
        self::getListByName($bar);
        self::getLists();
        self::getMailChimp();
        self::getOfficialMailChimp();
        ...
    }
}

// OR

Any::getListByName($bar);
Any::getLists();
Any::getMailChimp();
...
```
