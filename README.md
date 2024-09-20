# Dokuwiki robot404 plugin

I see no reason for webcrawlers to visit and index certain actions, such as login and search. The plugin configuration allows you to select which actions should be considered as disallowed for robots. Actions that are disabled in dokuwiki are already considered disallowed for robots. 

Disallowed actions will produce 404 when the user agent is detected to be a robot. Actions that are disabled in dokuwiki normally just display a message; webcrawlers will still index the resulting page and also visit these pages again in the future. 

Whether the client is a robot is determined using HTTP_USER_AGENT header. For testing purposes, you can add ?isrobot404=1 to the URL to simulate a robot visit.
