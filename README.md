# githubIssueImporter

Just a little script to auto-import gitHub-issues from

* Plain-text (or MarkDown) e-mails on a IMAPs-server
* RSS-feed (still WIP)
* Nicely parsed call-notifications of the german callcenter DiConn.de

### Example DiConn.de-Call-Notification

If I miss a phone call and our call-center DiConn.de is processing the call i'm receiving the following mail-notification

<img src="/screens/outlook_diconn_notification.png" width="500">

Mails like this will be automaticly imported from a IMAPs-Account, parsed and posted as gitHub-Ticket (by using a cronjob)

<img src="/screens/github_ticket.png" width="500">
