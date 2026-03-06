<?php
/*
 * Copyright (C) 2026 SYSTOPIA GmbH
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation in version 3.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types = 1);

namespace Civi\Mailbatch;

class MailUtils {

  /**
   * Get a list of the available/allowed sender email addresses
   *
   * @phpstan-return array<int, string>
   */
  public static function getSenderOptions(bool $asHtml = TRUE): array {
    // TODO: Remove check when minimum core version requirement is >= 6.0.0.
    if (class_exists('\Civi\Api4\SiteEmailAddress')) {
      $fromEmailAddresses = \Civi\Api4\SiteEmailAddress::get(FALSE)
        ->addSelect('display_name', 'email', 'id')
        ->addWhere('domain_id', '=', 'current_domain')
        ->addWhere('is_active', '=', TRUE)
        ->addOrderBy('is_default', 'DESC')
        ->execute()
        ->indexBy('id')
        ->getArrayCopy();
      // Include "email" column as the option value label did.
      $fromEmailAddresses = array_map(
        fn($address) => sprintf(
          '"%s" <%s>',
          $address['display_name'],
          $address['email']
        ),
        $fromEmailAddresses
      );
    }
    else {
      $fromEmailAddresses = OptionValue::get(FALSE)
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
      ? array_map(fn($label) => htmlspecialchars($label), $fromEmailAddresses)
      : $fromEmailAddresses;
  }

}
