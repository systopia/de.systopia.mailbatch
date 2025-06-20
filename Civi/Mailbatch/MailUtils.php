<?php

namespace Civi\Mailbatch;

class MailUtils {

  /**
   * Get a list of the available/allowed sender email addresses
   */
  public static function getSenderOptions($asHtml = TRUE) {
    // TODO: Remove check when minimum core version requirement is >= 6.0.0.
    if (class_exists('\Civi\Api4\SiteEmailAddress')) {
      $from_email_addresses = \Civi\Api4\SiteEmailAddress::get(FALSE)
        ->addSelect('display_name', 'email', 'id')
        ->addWhere('domain_id', '=', 'current_domain')
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('is_default', 'DESC')
        ->execute()
        ->indexBy('id')
        ->getArrayCopy();
      // Include "email" column as the option value label did.
      $from_email_addresses = array_map(
        fn($address) => sprintf(
          '"%s" <%s>',
          $address['display_name'],
          $address['email']
        ),
        $from_email_addresses
      );
    }
    else {
      $from_email_addresses = OptionValue::get(FALSE)
        ->addSelect('value', 'label')
        ->addWhere('domain_id', '=', 'current_domain')
        ->addWhere('option_group_id:name', '=', 'from_email_address')
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('is_default', 'DESC')
        ->execute()
        ->indexBy('value')
        ->column('label');
    }

    return $asHtml
      ? array_map(fn($label) => htmlspecialchars($label), $from_email_addresses)
      : $from_email_addresses;
  }

}
