#!/bin/bash
EMAIL=d.rijpkema@bluem.nl
export $(cat .env | xargs)
echo "Loaded keys: ${MAILJET_API_KEY} ${MAILJET_SECRET_KEY}"
curl -s \
	  -X POST \
	  --user "$MAILJET_API_KEY:$MAILJET_SECRET_KEY" \
	  "https://api.mailjet.com/v3.1/send" \
	  -H "Content-Type: application/json" \
	  -d '{"Messages":[{"From":{"Email":"d.rijpkema@bluem.nl","Name":"Your Name"},"To":[{"Email":"pluginsupport@bluem.nl","Name":"Pluginsupport"}],"Subject":"Release Completed","TextPart":"Release $(NEW_TAG) of $(PLUGIN_SLUG) has been completed. (test)"}]}' \
	  && echo "\nNotification email sent to $EMAIL}"
