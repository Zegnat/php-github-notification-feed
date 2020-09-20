# GitHub Notification Feed

Generating a feed from GitHub notifications.

## Deploying

I use Dokku to deploy stand-alone applications like this to a server. The following are the steps I follow to do this, similar steps might work for you.

As this application is released on Dokku through Herokuish, it is probably not to far away from being a one-click deploy on Heroku. But I am leaving that for future me to figure out.

```bash
dokku apps:create gnf
dokku config:set --no-restart gnf GITHUB_USERNAME="Zegnat" GITHUB_SECRET_TOKEN="65ba4c0010d88f30d93ff5aa38148cfa3ecee767"
git remote add dokku dokku.example.com:gnf
git push dokku main:master
```
