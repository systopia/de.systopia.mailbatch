# CiviCRM Extension MailBatch (de.systopia.mailbatch)

## What is MailBatch?

MailBatch is an extension of CiviCRM with which e-mails can be sent to a previously defined selection of contacts. MailBatch can not only be used for contacts, but also for a selection of donations. For example, an appropriate number of donations can be selected and a thank you letter sent to the corresponding donors.

If you use MailBatch, you are no longer bound by the standard restriction of not sending more than 50 mails at a time.

MailBatch performs a plausibility check before sending the mails. MailBatch checks whether an email address is stored for the contact, whether the correct address category is available and whether emails can be sent to the contact at all. 

With MailBatch you can or must define various settings with regard to the e-mail addresses and set the desired batch size. Some settings are mandatory fields and are marked with an "asterisk". In addition, in MailBatch you normally use one of the message templates that you have previously created in CiviCRM. 

The MailBatch extension is closely related to the MailAttachments extension. The extension MailAttachments enables a file to be attached to a mail. Different attachment types are distinguished: File On Server, CiviOffice Document, Contribution Invoice.

CiviCRM documents the sending of mails analogous to other activities of the system. You can determine whether the sent mail is recorded as an individual activity for the respective contact or whether the sending of the mail to all contacts is saved as an activity for the sender. In addition, MailBatch can distinguish between different events with regard to the activity: Sending successful, Sending failed, etc. 


## Installation

Requirements:
Which CiviOffice version is required for installation?
MailBatch only works if the MailAttachment extension is installed.


## Features
* Send mails to selected contacts without restriction
* Checking whether a contact or the mail addressee has a valid e-mail address
* Sending e-mails for selected donations with the corresponding system-generated invoice in the attachment
* Logging of the result of the e-mail dispatch in the form of an activity 

## Usage

### Selecting contacts

If you are working with MailBatch, the first step is to select the desired contacts to whom you want to send a mail.

* Filter out and select the desired contacts using a search function.
* From the **Action** list box, select the function **Send emails (via MailBatch)**.

### Choosing mail Settings

In MailBatch, you must or can specify certain settings with regard to the mail addresses and batch size.

* Select the desired sender mail address. The sender field is a required field.
* Decide whether you want to take a contact in copy or blind copy.
* Enter the desired reply mail address, if applicable.
* Select the desired batch size. This information is mandatory. 


### Selecting a message template

You generally create message templates in CiviCRM via the **Mailing templates** function from the **Mailings** menu.

* Select the appropriate template for the mails. 



### Specifying file attachment

With MailBatch you can specify different mail attachments for the e-mails to be sent. If the desired mail attachment is not found or cannot be attached, you can allow MailBatch to send the mail anyway.

* Select the appropriate attachment type for the mails (File On Server, Civi document ...). 


### Defining activities

In Mailbatch you can define in which way the sending of the mails is treated as an activity. 

* If necessary, define the activity if the sending of the e-mail was successful. 
* Specify the name of this activity.
* If necessary, set the activity if the e-mail was not sent successfully.
* Specify the name of this activity, if applicable.
* If necessary, set the activity if there was no e-mail address for a selected contact.
* Specify whether you want to assign the activity to a specific person, if applicable.
* Set the structure of the activity.

