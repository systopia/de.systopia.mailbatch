# MailBatch (`de.systopia.mailbatch`)

MailBatch is an extension for CiviCRM which allows sending e-mail to a
previously defined selection of contacts. MailBatch can not only be used for
contacts, but also for a selection of donations. For example, an appropriate
number of donations can be selected and a thank you letter be sent to the
corresponding donors.

If you use MailBatch, you are no longer bound by the standard restriction of not
sending more than 50 mails at a time.

MailBatch performs a plausibility check before sending the mails. MailBatch
checks whether an e-mail address is stored for the contact, whether the correct
address category is available and whether e-mail can be sent to the contact at
all.

With MailBatch you can define various settings with regard to the e-mail
addresses and set the desired batch size. Some settings are mandatory fields.
In addition, in MailBatch you normally use one of the message templates that you
have previously created in CiviCRM.

The MailBatch extension is closely related to the [*Mail
Attachments* extension](https://github.com/systopia/de.systopia.mailattachment)
which allows files be attached to an e-mail message, e. g. a static file on the
local server (optionally with token replacement in file names), a contribution
invoice, or a  *[CiviOffice](https://github.com/systopia/de.systopia.civioffice)
Document*.

CiviCRM documents the sending of e-mail analogous to other activities of the
system. You can determine whether the sent mail is recorded as an individual
activity for the respective contact or whether the sending of the mail to all
contacts is saved as an activity for the sender. In addition, MailBatch can
distinguish between different events with regard to the activity: Sending
successful, Sending failed, etc.
