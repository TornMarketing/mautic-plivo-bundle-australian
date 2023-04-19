# Mautic Plivo Plugin

Plivo SMS Transport Integration for Mautic

For more info about Plivo follow https://www.plivo.com/

https://www.plivo.com/docs/sms/api/message#send-a-message

**Why Plivo**
- Its cheaper than Twilio (50% on outbound and free on inbound)
- You can change the sender ID without verification (country dependent)

## Things to do Resolve/Enhance
- mautic.WARNING: PHP Warning - Trying to access array offset on value of type null
/app/bundles/SmsBundle/Form/Type/SmsSendType.php - at line 75

- setup a means of changing the source sender ID based on user or a field on the contact record

- setup a means of changing the e164 phone number country code per contact, currently coded to a plugin param

- unsubscription handling - I personally use n8n for this

- plivo delivery response handling

## Torn Marketing 
Shared with love from the Torn Marketing Team
https://tornmarketing.com.au
