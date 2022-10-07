# CiviCRM Extension MailBatch (de.systopia.mailbatch)

## What is MailBatch?

MailBatch is an extension of CiviCRM with which e-mails can be sent to a previously defined selection of contacts. MailBatch can not only be used for contacts, but also for a selection of donations. For example, an appropriate number of donations can be selected and a thank you letter sent to the corresponding donors.

If you use MailBatch, you are no longer bound by the standard restriction of not sending more than 50 mails at a time.

MailBatch performs a plausibility check before sending the mails. MailBatch checks whether an email address is stored for the contact, whether the correct address category is available and whether emails can be sent to the contact at all. 

With MailBatch you can or must define various settings with regard to the e-mail addresses and set the desired batch size. Some settings are mandatory fields and are marked with an "asterisk". In addition, in MailBatch you normally use one of the message templates that you have previously created in CiviCRM. 

The MailBatch extension is closely related to the MailAttachments extension. The extension MailAttachments enables a file to be attached to a mail. Different attachment types are distinguished: File On Server, CiviOffice Document, Contribution Invoice.

CiviCRM documents the sending of mails analogous to other activities of the system. You can determine whether the sent mail is recorded as an individual activity for the respective contact or whether the sending of the mail to all contacts is saved as an activity for the sender. In addition, MailBatch can distinguish between different events with regard to the activity: Sending successful, Sending failed, etc. 


## Documentation
- https://docs.civicrm.org/mailbatch/en/latest
- https://docs.civicrm.org/mailbatch/de/latest